<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Drink extends Model
{
     protected $fillable = [
        'base_price_soles',
        'total_price_soles',
        'drink_name',
        'drink_combination',
        'is_active'
    ];


    protected $casts = [
        'base_price_soles' => 'decimal:2',
        'total_price_soles' => 'decimal:2',
        'drink_combination' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones correctas
    public function basesdrinks()
    {
        return $this->belongsToMany(Basedrink::class, 'basedrink_drink', 'drink_id', 'basedrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }

    public function flavordrinks()
    {
        return $this->belongsToMany(Flavordrink::class, 'flavordrink_drink', 'drink_id', 'flavordrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }

    public function typesdrinks()
    {
        return $this->belongsToMany(Typedrink::class, 'typedrink_drink', 'drink_id', 'typedrink_id')
            ->withTimestamps()
            ->withPivot('id');
    }


    public function users()
    {
        return $this->belongsToMany(User::class, 'drink_user', 'drink_id', 'user_id')
            ->withTimestamps()
            ->withPivot('quantity', 'classschedule_id');
    }

      public function userFavorites(): BelongsToMany
    {
        return $this->morphToMany(UserFavorite::class, 'favoritable', 'user_favorites', 'favoritable_id', 'user_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }

    public function juiceCartCodes(): BelongsToMany
    {
        return $this->belongsToMany(JuiceCartCodes::class, 'juice_cart_drink', 'drink_id', 'juice_cart_code_id')
            ->withTimestamps();
    }

    /**
     * Generar nombre completo de la bebida basado en sus ingredientes
     */
    public function generateDrinkName(): string
    {
        $parts = [];

        // Agregar base
        if ($this->basesdrinks->isNotEmpty()) {
            $parts[] = $this->basesdrinks->pluck('name')->join(', ');
        }

        // Agregar sabor
        if ($this->flavordrinks->isNotEmpty()) {
            $parts[] = $this->flavordrinks->pluck('name')->join(', ');
        }

        // Agregar tipo
        if ($this->typesdrinks->isNotEmpty()) {
            $parts[] = $this->typesdrinks->pluck('name')->join(', ');
        }

        return implode(' + ', $parts) ?: 'Bebida Personalizada';
    }

    /**
     * Generar información de combinación para historial
     */
    public function generateDrinkCombination(): array
    {
        return [
            'bases' => $this->basesdrinks->map(function ($base) {
                return [
                    'id' => $base->id,
                    'name' => $base->name
                ];
            }),
            'flavors' => $this->flavordrinks->map(function ($flavor) {
                return [
                    'id' => $flavor->id,
                    'name' => $flavor->name
                ];
            }),
            'types' => $this->typesdrinks->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'price' => $type->price_soles ?? 0
                ];
            })
        ];
    }

    /**
     * Calcular precio total de la bebida
     * Solo se toma en cuenta el precio del typedrink
     */
    public function calculateTotalPrice(): float
    {
        $totalPrice = 0;

        // Precio base
        $totalPrice += $this->base_price_soles;

        // Solo sumar precio de typedrinks (de ahí se jala la información de precio)
        $totalPrice += $this->typesdrinks->sum('price');

        return $totalPrice;
    }

    /**
     * Boot method para generar automáticamente nombre y precio
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($drink) {
            // Generar nombre si no existe
            if (empty($drink->drink_name)) {
                $drink->updateQuietly([
                    'drink_name' => $drink->generateDrinkName(),
                    'drink_combination' => $drink->generateDrinkCombination(),
                    'total_price_soles' => $drink->calculateTotalPrice()
                ]);
            }
        });
    }

}
