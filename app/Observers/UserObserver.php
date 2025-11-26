<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\InvalidRequestException;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Verificar si el email fue verificado (nuevo o re-verificado)
        if (
            $user->isDirty('email_verified_at')
            && $user->email_verified_at !== null
        ) {
            $this->ensureStripeCustomer($user);
        }
    }

    /**
     * Asegura que el usuario tenga un cliente de Stripe válido.
     * Si no tiene stripe_id o el cliente no existe en Stripe, lo crea.
     * 
     * @param User $user
     * @return void
     */
    public function ensureStripeCustomer(User $user): void
    {
        try {
            // Si no tiene stripe_id, crear cliente directamente
            if (!$user->hasStripeId()) {
                Log::info('Creando cliente de Stripe para usuario', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                $user->createAsStripeCustomer();
                
                Log::info('Cliente de Stripe creado exitosamente', [
                    'user_id' => $user->id,
                    'stripe_id' => $user->stripe_id,
                ]);
                return;
            }

            // Si tiene stripe_id, verificar que el cliente exista en Stripe
            $stripeId = $user->stripe_id;
            
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $customer = $stripe->customers->retrieve($stripeId);
                
                // Si el cliente existe y no está eliminado, todo está bien
                if ($customer && !$customer->deleted) {
                    Log::info('Cliente de Stripe ya existe y es válido', [
                        'user_id' => $user->id,
                        'stripe_id' => $stripeId,
                    ]);
                    return;
                }
            } catch (InvalidRequestException $e) {
                // Si el cliente no existe (error 404), limpiar stripe_id y crear uno nuevo
                if ($e->getCode() === 404 || str_contains($e->getMessage(), 'No such customer')) {
                    Log::warning('Cliente de Stripe no existe en Stripe, limpiando stripe_id y creando uno nuevo', [
                        'user_id' => $user->id,
                        'stripe_id' => $stripeId,
                        'error' => $e->getMessage(),
                    ]);

                    // Limpiar stripe_id
                    $user->stripe_id = null;
                    $user->saveQuietly(); // Guardar sin disparar eventos

                    // Crear nuevo cliente
                    $user->createAsStripeCustomer();
                    
                    Log::info('Nuevo cliente de Stripe creado después de limpiar stripe_id inválido', [
                        'user_id' => $user->id,
                        'new_stripe_id' => $user->stripe_id,
                    ]);
                    return;
                }
                
                // Si es otro error, relanzarlo
                throw $e;
            }

        } catch (\Throwable $e) {
            Log::error('Error al asegurar cliente de Stripe', [
                'user_id' => $user->id,
                'email' => $user->email,
                'stripe_id' => $user->stripe_id ?? 'null',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

