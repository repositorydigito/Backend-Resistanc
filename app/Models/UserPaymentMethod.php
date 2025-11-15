<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_type',
        'provider',
        'card_brand',           // Cambio: provider -> card_brand
        'card_last_four',       // Cambio: account_identifier -> card_last_four
        'card_holder_name',     // ✅ Igual
        'card_expiry_month',    // Nuevo
        'card_expiry_year',     // Nuevo
        'bank_name',
        'account_number_masked',
        'is_default',          // ✅ Igual
        'is_saved_for_future',
        'gateway_token',
        'gateway_customer_id',
        'billing_address',
        'status',              // Cambio: is_active -> status
        'verification_status',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'card_expiry_month' => 'integer',
        'card_expiry_year' => 'integer',
        'is_default' => 'boolean',
        'is_saved_for_future' => 'boolean',
        'last_used_at' => 'datetime',
        'metadata' => 'array',
        'billing_address' => 'array',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_saved_for_future' => true,
        'status' => 'active',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Accessors
    public function getMaskedAccountAttribute()
    {
        return '**** **** **** ' . $this->card_last_four;
    }

    public function getExpiryDateAttribute()
    {
        if ($this->card_expiry_month && $this->card_expiry_year) {
            return sprintf('%02d/%d', $this->card_expiry_month, $this->card_expiry_year);
        }
        return null;
    }

    public function getIsExpiredAttribute()
    {
        if (!$this->card_expiry_month || !$this->card_expiry_year) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::create($this->card_expiry_year, $this->card_expiry_month)->endOfMonth();
        return $expiryDate < now();
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    // Constantes
    const PAYMENT_TYPES = [
        'credit_card' => 'Tarjeta de Crédito',
        'debit_card' => 'Tarjeta de Débito',
        'bank_transfer' => 'Transferencia Bancaria',
        'digital_wallet' => 'Billetera Digital',
        'cash' => 'Efectivo',
    ];

    const CARD_BRANDS = [
        'visa' => 'Visa',
        'mastercard' => 'Mastercard',
        'amex' => 'American Express',
        'dinners' => 'Diners Club',
    ];

    const STATUSES = [
        'active' => 'Activo',
        'expired' => 'Expirado',
        'blocked' => 'Bloqueado',
        'pending' => 'Pendiente',
    ];
}
