<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Studio extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'max_capacity',
        'equipment_available',
        'amenities',
        'studio_type',
        'is_active',
    ];

    protected $casts = [
        'equipment_available' => 'array',
        'amenities' => 'array',
        'max_capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the classes held in this studio.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'studio_id');
    }

    /**
     * Get the class schedules for this studio.
     */
    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'studio_id');
    }

    /**
     * Scope to get only active studios.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by studio type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('studio_type', $type);
    }

    /**
     * Get the equipment available as a formatted string.
     */
    public function getEquipmentAvailableStringAttribute(): string
    {
        if (!$this->equipment_available) {
            return '';
        }

        return implode(', ', $this->equipment_available);
    }

    /**
     * Get the amenities as a formatted string.
     */
    public function getAmenitiesStringAttribute(): string
    {
        if (!$this->amenities) {
            return '';
        }

        return implode(', ', $this->amenities);
    }

    /**
     * Check if studio is suitable for a specific discipline.
     */
    public function isSuitableForDiscipline(string $disciplineName): bool
    {
        return match ($disciplineName) {
            'cycling' => $this->studio_type === 'cycling',
            'solidreformer' => $this->studio_type === 'reformer',
            'pilates_mat', 'yoga', 'barre' => in_array($this->studio_type, ['mat', 'multipurpose']),
            default => $this->studio_type === 'multipurpose',
        };
    }
}
