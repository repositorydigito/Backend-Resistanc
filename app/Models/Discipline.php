<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'display_name',
        'description',
        'icon_url',
        'image_url',
        'image_seat',
        'color_hex',
        'equipment_required',
        'difficulty_level',
        'calories_per_hour_avg',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'equipment_required' => 'array',
        'is_active' => 'boolean',
        'calories_per_hour_avg' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the instructors that teach this discipline.
     */
    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'instructor_discipline');
    }

    /**
     * Get the classes for this discipline.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'discipline_id');
    }

    /**
     * Scope to get only active disciplines.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the equipment required as a formatted string.
     */
    public function getEquipmentRequiredStringAttribute(): string
    {
        if (!$this->equipment_required) {
            return '';
        }

        return implode(', ', $this->equipment_required);
    }

    /**
     * Get the display name or fallback to name.
     */
    public function getDisplayNameAttribute($value): string
    {
        return $value ?: $this->name;
    }
    public function packages()
    {
        return $this->belongsToMany(Package::class);
    }

    // Tablas polimórficas
    public function membresiables(): BelongsToMany
    {
        return $this->morphToMany(Membership::class, 'membresiable')
            ->withPivot('quantity', 'is_active', 'has_image', 'image_path', 'unlimited')
            ->withTimestamps();
    }

    public function memberships()
    {
        return $this->belongsTo(Membership::class);
    }
}
