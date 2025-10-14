<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceOrderDetail extends Model
{

    protected $fillable = [
        'juice_order_id',
        'drink_id',
        'quantity',
        'drink_name',
        'drink_combination',
        'unit_price_soles',
        'total_price_soles',
        'special_instructions',
        'ingredients_info'
    ];

    protected $casts = [
        'drink_combination' => 'array',
        'unit_price_soles' => 'decimal:2',
        'total_price_soles' => 'decimal:2',
        'ingredients_info' => 'array'
    ];

    public function juiceOrder()
    {
        return $this->belongsTo(JuiceOrder::class);
    }

    public function drink()
    {
        return $this->belongsTo(Drink::class);
    }

    /**
     * Calculate total price for this detail
     */
    public function calculateTotalPrice(): float
    {
        return $this->unit_price_soles * $this->quantity;
    }

    /**
     * Boot method to calculate total price
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            if (empty($detail->total_price_soles) && $detail->unit_price_soles && $detail->quantity) {
                $detail->total_price_soles = $detail->calculateTotalPrice();
            }
        });
    }
}
