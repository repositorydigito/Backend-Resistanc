<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_type' => $this->payment_type,
            'card_brand' => $this->card_brand,
            'card_last_four' => $this->card_last_four,
            'card_holder_name' => $this->card_holder_name,
            'expiry_date' => $this->expiry_date, // Usa el accessor del modelo
            'is_expired' => $this->is_expired, // Usa el accessor del modelo
            'bank_name' => $this->bank_name,
            'account_number_masked' => $this->account_number_masked,
            'is_default' => $this->is_default,
            'is_saved_for_future' => $this->is_saved_for_future,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'billing_address' => $this->billing_address,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
