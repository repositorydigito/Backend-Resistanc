<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'user_id' => $this->user_id,
            'payment_type' => $this->payment_type,
            'provider' => $this->provider,
            
            // Información de tarjeta (enmascarada)
            'card_last_four' => $this->card_last_four ? '****' . $this->card_last_four : null,
            'card_brand' => $this->card_brand,
            'card_holder_name' => $this->card_holder_name,
            'card_expiry_month' => $this->card_expiry_month,
            'card_expiry_year' => $this->card_expiry_year,
            
            // Información bancaria (enmascarada)
            'bank_name' => $this->bank_name,
            'account_number_masked' => $this->account_number_masked,
            
            // Configuración
            'is_default' => (bool) $this->is_default,
            'is_saved_for_future' => (bool) $this->is_saved_for_future,
            
            // Estado
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'last_used_at' => $this->last_used_at?->toISOString(),
            
            // Información adicional
            'billing_address' => $this->billing_address,
            'metadata' => $this->metadata,
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Información formateada para la UI
            'display_name' => $this->getDisplayName(),
            'is_expired' => $this->isExpired(),
            'can_use' => $this->canUse(),
        ];
    }
    
    /**
     * Obtiene el nombre de visualización del método de pago
     */
    private function getDisplayName(): string
    {
        if ($this->payment_type === 'credit_card' || $this->payment_type === 'debit_card') {
            $brand = $this->card_brand ? ucfirst($this->card_brand) : 'Tarjeta';
            $lastFour = $this->card_last_four ? ' ****' . $this->card_last_four : '';
            return $brand . $lastFour;
        }
        
        if ($this->payment_type === 'bank_transfer') {
            return $this->bank_name ?: 'Transferencia bancaria';
        }
        
        if ($this->payment_type === 'digital_wallet') {
            return $this->provider ? ucfirst($this->provider) : 'Billetera digital';
        }
        
        return ucfirst($this->payment_type);
    }
    
    /**
     * Verifica si la tarjeta está expirada
     */
    private function isExpired(): bool
    {
        if (!$this->card_expiry_month || !$this->card_expiry_year) {
            return false;
        }
        
        $expiryDate = \Carbon\Carbon::createFromDate($this->card_expiry_year, $this->card_expiry_month, 1);
        return $expiryDate->endOfMonth()->isPast();
    }
    
    /**
     * Verifica si el método de pago se puede usar
     */
    private function canUse(): bool
    {
        return $this->status === 'active' && 
               $this->verification_status === 'verified' && 
               !$this->isExpired();
    }
}
