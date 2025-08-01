<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceCartCodes extends Model
{

    protected $table = 'juice_cart_codes';

    protected $fillable = ['code'];

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'juice_cart_drink', 'juice_cart_code_id', 'drink_id');
    }
}
