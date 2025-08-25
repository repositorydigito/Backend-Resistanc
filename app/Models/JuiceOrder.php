<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuiceOrder extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
    ];

    /**
     * Get the user that owns the juice order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

   /**
    * Get the details for the juice order.
    */
   public function details()
   {
       return $this->hasMany(JuiceOrderDetail::class);
   }
}
