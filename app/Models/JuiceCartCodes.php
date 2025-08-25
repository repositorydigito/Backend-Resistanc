<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceCartCodes extends Model
{

    protected $table = 'juice_cart_codes';

    protected $fillable = [
        'code',
        'user_id',
        'is_used',
        'juice_order_id',
    ];

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'juice_cart_drink', 'juice_cart_code_id', 'drink_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function juiceOrder()
    {
        return $this->belongsTo(JuiceOrder::class, 'juice_order_id');
    }


}
