<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (
            $user->isDirty('email_verified_at')
            && $user->email_verified_at !== null
            && !$user->hasStripeId()
        ) {
            try {
                $user->createAsStripeCustomer();
            } catch (\Throwable $e) {
                Log::error('No se pudo crear el cliente de Stripe tras verificar email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

