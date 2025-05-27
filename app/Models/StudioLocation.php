<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StudioLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address_line',
        'city',
        'country',
        'latitude',
        'longitude',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    /**
     * Get the studios at this location.
     */
    public function studios(): HasMany
    {
        return $this->hasMany(Studio::class, 'location_id');
    }

    /**
     * Scope to get only active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line,
            $this->city,
            $this->country === 'PE' ? 'Perú' : $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get the Google Maps URL for this location.
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        if (!$this->latitude || !$this->longitude) {
            return '';
        }

        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }
}
