<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo dinámico para representar transacciones/pagos de Stripe
 * No se guarda en la base de datos, solo se usa para mostrar datos en Filament
 */
class StripeTransaction extends Model
{
    /**
     * No usar tabla real, solo para representación
     */
    protected $table = 'stripe_transactions';

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
        'type',
        'status',
        'amount',
        'currency',
        'description',
        'payment_method',
        'payment_method_type',
        'invoice_id',
        'subscription_id',
        'charge_id',
        'created_at_stripe',
        'metadata',
        'receipt_url',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at_stripe' => 'datetime',
        'metadata' => 'array',
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


