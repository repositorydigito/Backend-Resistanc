<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basedrink extends Model
{
   protected $fillable = [
        'name',
        'image_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'basedrink_drink', 'basedrink_id', 'drink_id')
            ->withTimestamps()
            ->withPivot('id');
    }
}
