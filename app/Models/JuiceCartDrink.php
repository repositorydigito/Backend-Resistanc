<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceCartDrink extends Model
{

    protected $table = 'juice_cart_drink';

    protected $fillable = [
        'juice_cart_code_id',
        'drink_id',
        'quantity',
    ];

    public function juiceCartCode()
    {
        return $this->belongsTo(JuiceCartCodes::class, 'juice_cart_code_id');
    }

    public function drink()
    {
        return $this->belongsTo(Drink::class, 'drink_id');
    }

}
