<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Typedrink extends Model
{
    protected $fillable = [
        'name',
        'image_url',
        'ico_url',
        'price',
        'is_active'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'typedrink_drink', 'typedrink_id', 'drink_id')
            ->withTimestamps()
            ->withPivot('id');
    }

    // public function memberships()
    // {
    //     return $this->belongsTo(Membership::class);
    // }
}
