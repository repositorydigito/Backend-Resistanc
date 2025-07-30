<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Towel extends Model
{
    protected $fillable = [
        'code',
        'size',
        'color',
        'gender',

        'description',
        'observations',
        'status',
        'image',
        'images_gallery',

        // Relaciones
        'user_id',
        'user_updated_id',
    ];

    protected $casts = [
        'images_gallery' => 'array', // Convierte automáticamente JSON ↔ array
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userUpdated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_updated_id');
    }

    // Relaciones para préstamos
    public function loans(): HasMany
    {
        return $this->hasMany(TowelLoan::class);
    }

    public function activeLoan(): HasMany
    {
        return $this->hasMany(TowelLoan::class)->where('status', 'in_use');
    }
}
