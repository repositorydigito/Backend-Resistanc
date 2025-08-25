<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceOrderDetail extends Model
{

    protected $fillable = [
        'juice_order_id',
        'drink_id',
        'quantity',
    ];

    public function juiceOrder()
    {
        return $this->belongsTo(JuiceOrder::class);
    }

    public function drink()
    {
        return $this->belongsTo(Drink::class);
    }

}
