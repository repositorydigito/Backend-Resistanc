<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // Helper method to get image URL
    // public function getImageUrlAttribute(): string
    // {
    //     if (!$this->image) {
    //         return '/image/logos/logoBlancoR.svg'; // Default image
    //     }

    //     if (filter_var($this->image, FILTER_VALIDATE_URL)) {
    //         return $this->image;
    //     }

    //     if (str_starts_with($this->image, '/')) {
    //         return $this->image;
    //     }

    //     return asset('storage/' . $this->image);
    // }
}
