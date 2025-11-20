<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo dinámico para representar métodos de pago de Stripe
 * No se guarda en la base de datos, solo se usa para mostrar datos en Filament
 */
class StripePaymentMethod extends Model
{
    /**
     * No usar tabla real, solo para representación
     */
    protected $table = 'stripe_payment_methods';

    /**
     * No guardar timestamps
     */
    public $timestamps = false;

    /**
     * No usar incrementing
     */
    public $incrementing = false;

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id';

    /**
     * Atributos que pueden ser asignados
     */
    protected $fillable = [
        'id',
        'stripe_id',
        'payment_type',
        'card_brand',
        'card_last_four',
        'card_holder_name',
        'expiry_date',
        'card_expiry_month',
        'card_expiry_year',
        'is_default',
        'status',
        'is_expired',
        'gateway_token',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_expired' => 'boolean',
        'card_expiry_month' => 'integer',
        'card_expiry_year' => 'integer',
    ];

    /**
     * Obtener la clave primaria
     */
    public function getKey()
    {
        return $this->getAttribute('id');
    }

    /**
     * Obtener el nombre de la clave primaria
     */
    public function getKeyName()
    {
        return 'id';
    }

    /**
     * Obtener la clave de ruta
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Siempre existe (es un modelo virtual)
     */
    public function exists(): bool
    {
        return true;
    }

    /**
     * Siempre está guardado (es un modelo virtual)
     */
    public function wasRecentlyCreated(): bool
    {
        return false;
    }
}

