<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo dinámico para representar suscripciones de Stripe
 * No se guarda en la base de datos, solo se usa para mostrar datos en Filament
 */
class StripeSubscription extends Model
{
    /**
     * No usar tabla real, solo para representación
     */
    protected $table = 'stripe_subscriptions';

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
        'status',
        'plan_name',
        'plan_id',
        'price_id',
        'amount',
        'currency',
        'interval',
        'interval_count',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'canceled_at',
        'trial_start',
        'trial_end',
        'created_at_stripe',
        'metadata',
    ];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     */
    protected $casts = [
        'cancel_at_period_end' => 'boolean',
        'amount' => 'decimal:2',
        'interval_count' => 'integer',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
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


