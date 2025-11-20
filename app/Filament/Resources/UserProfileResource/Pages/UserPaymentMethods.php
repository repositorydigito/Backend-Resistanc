<?php

namespace App\Filament\Resources\UserProfileResource\Pages;

use App\Filament\Resources\UserProfileResource;
use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Models\UserProfile;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class UserPaymentMethods extends Page
{
    protected static string $resource = UserProfileResource::class;

    protected static string $view = 'filament.resources.user-profile-resource.pages.user-payment-methods';

    protected static ?string $title = 'Métodos de Pago desde Stripe';

    public UserProfile $record;
    
    public Collection $paymentMethods;

    public function mount(int | string $record): void
    {
        $this->record = UserProfile::findOrFail($record);
        $this->loadPaymentMethods();
    }

    public function loadPaymentMethods(): void
    {
        if (!$this->record->user || !$this->record->user->stripe_id) {
            $this->paymentMethods = collect([]);
            return;
        }

        $this->paymentMethods = $this->getPaymentMethodsFromStripe($this->record->user);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action('loadPaymentMethods'),
            Actions\Action::make('back')
                ->label('Volver al Perfil')
                ->icon('heroicon-o-arrow-left')
                ->url(fn() => UserProfileResource::getUrl('edit', ['record' => $this->record])),
        ];
    }


    /**
     * Obtiene los métodos de pago directamente desde Stripe
     */
    private function getPaymentMethodsFromStripe(?User $user): \Illuminate\Support\Collection
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
            Log::error('Error al obtener métodos de pago desde Stripe en Filament', [
                'user_id' => $user->id ?? null,
                'stripe_id' => $user->stripe_id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return collect([]);
        } catch (\Exception $e) {
            Log::error('Error inesperado al obtener métodos de pago desde Stripe en Filament', [
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

    /**
     * Sobre escribir el método para paginar correctamente
     */
    protected function paginateTableQuery($query)
    {
        $page = request()->get('page', 1);
        $perPage = $this->tableRecordsPerPage;
        $offset = ($page - 1) * $perPage;
        
        if ($query instanceof \Illuminate\Support\Collection) {
            $items = $query->slice($offset, $perPage)->values();
            return new LengthAwarePaginator(
                $items,
                $query->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }
        
        return parent::paginateTableQuery($query);
    }
}
