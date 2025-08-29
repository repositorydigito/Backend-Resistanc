<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flavordrink extends Model
{
    protected $fillable = [
        'name',
        'image_url',
        'ico_url',
        'is_active'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'flavordrink_drink', 'flavordrink_id', 'drink_id')
            ->withTimestamps()
            ->withPivot('id');
    }
}
