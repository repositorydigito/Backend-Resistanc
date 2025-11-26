<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Models\StripePaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class PaymentMethodsRelationManager extends RelationManager
{
    // No usamos una relación real, obtenemos datos de Stripe
    protected static string $relationship = 'userPaymentMethods';

    protected static ?string $title = 'Métodos de Pago (Stripe)';

    protected static ?string $modelLabel = 'Método de Pago';

    protected static ?string $pluralModelLabel = 'Métodos de Pago';

    public function form(Form $form): Form
    {
        // No permitir crear/editar desde aquí, los métodos se manejan en Stripe
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Retornar una consulta vacía ya que usaremos datos de Stripe
                return $query->whereRaw('1 = 0');
            })
            ->columns([
                Tables\Columns\TextColumn::make('stripe_id')
                    ->label('ID Stripe')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Tipo de Pago')
                    ->formatStateUsing(fn($state) => UserPaymentMethod::PAYMENT_TYPES[$state] ?? ucfirst($state))
                    ->badge(),

                Tables\Columns\TextColumn::make('card_brand')
                    ->label('Marca de Tarjeta')
                    ->formatStateUsing(fn($state) => $state ? (UserPaymentMethod::CARD_BRANDS[strtolower($state)] ?? ucfirst($state)) : '-')
                    ->badge(),

                Tables\Columns\TextColumn::make('card_last_four')
                    ->label('Últimos 4 Dígitos')
                    ->formatStateUsing(fn($state) => $state ? '**** ' . $state : '-'),

                Tables\Columns\TextColumn::make('card_holder_name')
                    ->label('Nombre del Titular')
                    ->searchable(false),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Fecha de Expiración')
                    ->badge()
                    ->color(fn($record) => ($record->is_expired ?? false) ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Método Predeterminado')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'blocked' => 'danger',
                        'pending' => 'warning',
                        default => 'gray'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('card_brand')
                    ->options(UserPaymentMethod::CARD_BRANDS),
            ])
            ->headerActions([
                // No permitir crear desde aquí, se hace desde Stripe
            ])
            ->actions([
                // No permitir editar/eliminar desde aquí, se hace desde Stripe
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No permitir acciones masivas
                ]),
            ])
            ->emptyStateHeading('No hay métodos de pago')
            ->emptyStateDescription('Este usuario no tiene métodos de pago registrados en Stripe.')
            ->emptyStateIcon('heroicon-o-credit-card');
    }

    /**
     * Sobre escribimos getTableRecords para inyectar datos de Stripe
     */
    public function getTableRecords(): \Illuminate\Contracts\Pagination\Paginator
    {
        $user = $this->getOwnerRecord()->user;
        $stripePaymentMethods = $this->getPaymentMethodsFromStripe($user);
        
        // Crear modelos Eloquent basados en los datos de Stripe
        $records = collect();
        foreach ($stripePaymentMethods as $index => $methodData) {
            $model = new StripePaymentMethod();
            // Usar fill para establecer los atributos
            $model->fill([
                'id' => $index + 1, // ID numérico para Filament
                'stripe_id' => $methodData['id'],
                'payment_type' => $methodData['payment_type'],
                'card_brand' => $methodData['card_brand'],
                'card_last_four' => $methodData['card_last_four'],
                'card_holder_name' => $methodData['card_holder_name'],
                'expiry_date' => $methodData['expiry_date'],
                'card_expiry_month' => $methodData['card_expiry_month'],
                'card_expiry_year' => $methodData['card_expiry_year'],
                'is_default' => $methodData['is_default'],
                'status' => $methodData['status'],
                'is_expired' => $methodData['is_expired'],
                'gateway_token' => $methodData['gateway_token'],
            ]);
            // Marcar como existente para que Filament lo trate como modelo válido
            $model->syncOriginal();
            $records->push($model);
        }
        
        // Paginar manualmente
        $page = request()->get('page', 1);
        $perPage = $this->tableRecordsPerPage ?? 15;
        $offset = ($page - 1) * $perPage;
        $items = $records->slice($offset, $perPage)->values();
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $records->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Obtiene los métodos de pago directamente desde Stripe
     */
    private function getPaymentMethodsFromStripe(?User $user): Collection
    {
        if (!$user || !$user->stripe_id) {
            return collect([]);
        }

        try {
            $stripe = $this->makeStripeClient();
            
            // Obtener el método de pago predeterminado
            $stripeCustomer = $user->asStripeCustomer();
            $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method ?? null;

            // Intentar obtener métodos usando Cashier primero
            $cashierPaymentMethods = $user->paymentMethods('card');
            
            $paymentMethods = [];
            
            if ($cashierPaymentMethods->isEmpty()) {
                // Si Cashier no devuelve métodos, usar StripeClient directamente
                $stripePaymentMethodsResponse = $stripe->customers->allPaymentMethods(
                    $user->stripe_id,
                    ['type' => 'card']
                );
                
                foreach ($stripePaymentMethodsResponse->data as $stripeMethod) {
                    if ($stripeMethod->type === 'card' && isset($stripeMethod->card)) {
                        $paymentMethods[] = $this->transformStripePaymentMethod($stripeMethod, $defaultPaymentMethodId);
                    }
                }
            } else {
                // Usar métodos de Cashier
                foreach ($cashierPaymentMethods as $cashierMethod) {
                    $stripeMethod = $cashierMethod->asStripePaymentMethod();
                    if ($stripeMethod->type === 'card' && isset($stripeMethod->card)) {
                        $paymentMethods[] = $this->transformStripePaymentMethod($stripeMethod, $defaultPaymentMethodId);
                    }
                }
            }

            // Ordenar: primero el predeterminado
            usort($paymentMethods, function ($a, $b) {
                if ($a['is_default'] && !$b['is_default']) return -1;
                if (!$a['is_default'] && $b['is_default']) return 1;
                return 0;
            });

            return collect($paymentMethods);
            
        } catch (ApiErrorException $e) {
            Log::error('Error al obtener métodos de pago desde Stripe en Filament RelationManager', [
                'user_id' => $user->id ?? null,
                'stripe_id' => $user->stripe_id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return collect([]);
        } catch (\Exception $e) {
            Log::error('Error inesperado al obtener métodos de pago desde Stripe en Filament RelationManager', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return collect([]);
        }
    }

    /**
     * Transforma un método de pago de Stripe al formato esperado
     */
    private function transformStripePaymentMethod($stripeMethod, ?string $defaultPaymentMethodId): array
    {
        $card = $stripeMethod->card ?? null;
        $billingDetails = $stripeMethod->billing_details ?? null;

        return [
            'id' => $stripeMethod->id,
            'payment_type' => 'credit_card',
            'card_brand' => $card->brand ?? null,
            'card_last_four' => $card->last4 ?? null,
            'card_holder_name' => $billingDetails->name ?? null,
            'expiry_date' => ($card->exp_month && $card->exp_year)
                ? sprintf('%02d/%d', $card->exp_month, $card->exp_year)
                : null,
            'card_expiry_month' => $card->exp_month ?? null,
            'card_expiry_year' => $card->exp_year ?? null,
            'is_expired' => $this->isCardExpired($card->exp_month ?? null, $card->exp_year ?? null),
            'is_default' => ($stripeMethod->id === $defaultPaymentMethodId),
            'status' => 'active',
            'gateway_token' => $stripeMethod->id,
        ];
    }

    /**
     * Verifica si una tarjeta está expirada
     */
    private function isCardExpired(?int $expMonth, ?int $expYear): bool
    {
        if (!$expMonth || !$expYear) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::createFromDate($expYear, $expMonth, 1)->endOfMonth();
        return $expiryDate->isPast();
    }

    /**
     * Crea un cliente de Stripe
     */
    private function makeStripeClient(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (!$secret) {
            throw new \RuntimeException('Stripe no está configurado correctamente. Falta services.stripe.secret.');
        }

        return new StripeClient($secret);
    }
}
