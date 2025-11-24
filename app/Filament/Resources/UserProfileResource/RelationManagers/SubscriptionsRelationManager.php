<?php

namespace App\Filament\Resources\UserProfileResource\RelationManagers;

use App\Models\User;
use App\Models\StripeSubscription;
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

class SubscriptionsRelationManager extends RelationManager
{
    // No usamos una relación real, obtenemos datos de Stripe
    protected static string $relationship = 'userSubscriptions';

    protected static ?string $title = 'Suscripciones (Stripe)';

    protected static ?string $modelLabel = 'Suscripción';

    protected static ?string $pluralModelLabel = 'Suscripciones';

    public function form(Form $form): Form
    {
        // No permitir crear/editar desde aquí, las suscripciones se manejan en Stripe
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
                    ->sortable(false)
                    ->copyable(),

                Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan')
                    ->searchable(false)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled' => 'danger',
                        'unpaid' => 'danger',
                        'incomplete' => 'warning',
                        'incomplete_expired' => 'danger',
                        'paused' => 'gray',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'active' => 'Activa',
                        'trialing' => 'En Prueba',
                        'past_due' => 'Vencida',
                        'canceled' => 'Cancelada',
                        'unpaid' => 'No Pagada',
                        'incomplete' => 'Incompleta',
                        'incomplete_expired' => 'Incompleta Expirada',
                        'paused' => 'Pausada',
                        default => ucfirst($state ?? 'Desconocido')
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('usd', divideBy: 100)
                    ->sortable(false),

                Tables\Columns\TextColumn::make('interval')
                    ->label('Frecuencia')
                    ->formatStateUsing(function($state, $record) {
                        if (!$state || !$record) return '-';
                        $intervalCount = $record->interval_count ?? 1;
                        if ($intervalCount > 1) {
                            $period = $state === 'month' ? 'meses' : ($state === 'year' ? 'años' : 'días');
                            return "Cada {$intervalCount} {$period}";
                        }
                        if ($state === 'month') return 'Mensual';
                        if ($state === 'year') return 'Anual';
                        if ($state === 'day') return 'Diario';
                        return ucfirst($state);
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('current_period_start')
                    ->label('Inicio del Período')
                    ->dateTime('d/m/Y')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('current_period_end')
                    ->label('Fin del Período')
                    ->dateTime('d/m/Y')
                    ->sortable(false)
                    ->color(fn($record) => $record && $record->current_period_end && $record->current_period_end->isPast() ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('cancel_at_period_end')
                    ->label('Cancelar al Finalizar')
                    ->boolean(),

                Tables\Columns\TextColumn::make('trial_end')
                    ->label('Fin de Prueba')
                    ->dateTime('d/m/Y')
                    ->sortable(false)
                    ->placeholder('-')
                    ->visible(fn($record) => $record && isset($record->trial_end) && $record->trial_end !== null),

                Tables\Columns\TextColumn::make('created_at_stripe')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activa',
                        'trialing' => 'En Prueba',
                        'past_due' => 'Vencida',
                        'canceled' => 'Cancelada',
                        'unpaid' => 'No Pagada',
                        'incomplete' => 'Incompleta',
                        'incomplete_expired' => 'Incompleta Expirada',
                        'paused' => 'Pausada',
                    ]),
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
            ->emptyStateHeading('No hay suscripciones')
            ->emptyStateDescription('Este usuario no tiene suscripciones registradas en Stripe.')
            ->emptyStateIcon('heroicon-o-credit-card');
    }

    /**
     * Sobre escribimos getTableRecords para inyectar datos de Stripe
     */
    public function getTableRecords(): \Illuminate\Contracts\Pagination\Paginator
    {
        $user = $this->getOwnerRecord()->user;
        $stripeSubscriptions = $this->getSubscriptionsFromStripe($user);

        // Crear modelos Eloquent basados en los datos de Stripe
        $records = collect();
        foreach ($stripeSubscriptions as $index => $subscriptionData) {
            $model = new StripeSubscription();
            // Usar fill para establecer los atributos, asegurando que todos existan
            $model->fill([
                'id' => $index + 1, // ID numérico para Filament
                'stripe_id' => $subscriptionData['id'] ?? null,
                'status' => $subscriptionData['status'] ?? null,
                'plan_name' => $subscriptionData['plan_name'] ?? 'N/A',
                'plan_id' => $subscriptionData['plan_id'] ?? null,
                'price_id' => $subscriptionData['price_id'] ?? null,
                'amount' => $subscriptionData['amount'] ?? 0,
                'currency' => $subscriptionData['currency'] ?? 'USD',
                'interval' => $subscriptionData['interval'] ?? null,
                'interval_count' => $subscriptionData['interval_count'] ?? 1,
                'current_period_start' => $subscriptionData['current_period_start'] ?? null,
                'current_period_end' => $subscriptionData['current_period_end'] ?? null,
                'cancel_at_period_end' => $subscriptionData['cancel_at_period_end'] ?? false,
                'canceled_at' => $subscriptionData['canceled_at'] ?? null,
                'trial_start' => $subscriptionData['trial_start'] ?? null,
                'trial_end' => $subscriptionData['trial_end'] ?? null,
                'created_at_stripe' => $subscriptionData['created_at_stripe'] ?? null,
                'metadata' => $subscriptionData['metadata'] ?? [],
            ]);
            // Establecer atributos directamente para asegurar que estén disponibles
            $model->setAttribute('trial_end', $subscriptionData['trial_end'] ?? null);
            $model->setAttribute('trial_start', $subscriptionData['trial_start'] ?? null);
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
     * Obtiene las suscripciones directamente desde Stripe
     */
    private function getSubscriptionsFromStripe(?User $user): Collection
    {
        if (!$user || !$user->stripe_id) {
            return collect([]);
        }

        try {
            $stripe = $this->makeStripeClient();

            // Obtener todas las suscripciones del usuario
            $stripeSubscriptions = $stripe->subscriptions->all([
                'customer' => $user->stripe_id,
                'status' => 'all', // Obtener todas: active, past_due, canceled, etc.
                'limit' => 100, // Límite razonable
            ]);

            $subscriptions = [];

            foreach ($stripeSubscriptions->data as $stripeSubscription) {
                $subscriptions[] = $this->transformStripeSubscription($stripeSubscription, $stripe);
            }

            // Ordenar: primero las activas, luego por fecha de creación descendente
            usort($subscriptions, function ($a, $b) {
                // Priorizar suscripciones activas
                if ($a['status'] === 'active' && $b['status'] !== 'active') return -1;
                if ($a['status'] !== 'active' && $b['status'] === 'active') return 1;

                // Luego por fecha de creación descendente
                $dateA = $a['created_at_stripe'] ? strtotime($a['created_at_stripe']) : 0;
                $dateB = $b['created_at_stripe'] ? strtotime($b['created_at_stripe']) : 0;
                return $dateB <=> $dateA;
            });

            return collect($subscriptions);

        } catch (ApiErrorException $e) {
            Log::error('Error al obtener suscripciones desde Stripe en Filament RelationManager', [
                'user_id' => $user->id ?? null,
                'stripe_id' => $user->stripe_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        } catch (\Exception $e) {
            Log::error('Error inesperado al obtener suscripciones desde Stripe en Filament RelationManager', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Transforma una suscripción de Stripe al formato esperado
     */
    private function transformStripeSubscription($stripeSubscription, StripeClient $stripe): array
    {
        $price = null;
        $product = null;
        $planName = 'N/A';

        // Obtener información del precio/producto
        if (isset($stripeSubscription->items->data[0]->price)) {
            $price = $stripeSubscription->items->data[0]->price;
            $priceId = $price->id;

            if (isset($price->product)) {
                $productId = is_string($price->product) ? $price->product : $price->product->id;
                try {
                    $product = $stripe->products->retrieve($productId);
                    $planName = $product->name ?? 'N/A';
                } catch (\Exception $e) {
                    // Si no se puede obtener el producto, usar el ID del precio
                    $planName = $price->nickname ?? $priceId;
                }
            } else {
                $planName = $price->nickname ?? $priceId;
            }
        }

        return [
            'id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
            'plan_name' => $planName,
            'plan_id' => $product->id ?? null,
            'price_id' => $price->id ?? null,
            'amount' => $price->unit_amount ?? 0,
            'currency' => strtoupper($price->currency ?? 'usd'),
            'interval' => $price->recurring->interval ?? null,
            'interval_count' => $price->recurring->interval_count ?? 1,
            'current_period_start' => $stripeSubscription->current_period_start
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)
                : null,
            'current_period_end' => $stripeSubscription->current_period_end
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)
                : null,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? false,
            'canceled_at' => $stripeSubscription->canceled_at
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->canceled_at)
                : null,
            'trial_start' => $stripeSubscription->trial_start
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_start)
                : null,
            'trial_end' => $stripeSubscription->trial_end
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end)
                : null,
            'created_at_stripe' => $stripeSubscription->created
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->created)
                : null,
            'metadata' => $stripeSubscription->metadata->toArray() ?? [],
        ];
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

