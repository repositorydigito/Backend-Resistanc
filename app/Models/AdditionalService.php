<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'price',
        'duration_minutes',
        'is_bookable_with_class',
        'is_standalone',
        'max_participants',
        'equipment_required',
        'prerequisites',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'equipment_required' => 'array',
        'prerequisites' => 'array',
        'is_bookable_with_class' => 'boolean',
        'is_standalone' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the booking extra options for this service.
     */
    public function bookingExtraOptions(): HasMany
    {
        return $this->hasMany(BookingExtraOption::class);
    }

    /**
     * Scope to get only active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get services bookable with classes.
     */
    public function scopeBookableWithClass($query)
    {
        return $query->where('is_bookable_with_class', true);
    }

    /**
     * Scope to get standalone services.
     */
    public function scopeStandalone($query)
    {
        return $query->where('is_standalone', true);
    }

    /**
     * Get the type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match ($this->type) {
            'massage' => 'Masaje',
            'nutrition' => 'Consulta Nutricional',
            'personal_training' => 'Entrenamiento Personal',
            'equipment_rental' => 'Alquiler de Equipos',
            'locker_rental' => 'Alquiler de Casillero',
            'towel_service' => 'Servicio de Toallas',
            default => ucfirst($this->type),
        ];
    }

    /**
     * Get the duration formatted.
     */
    public function getDurationFormattedAttribute(): string
    {
        if ($this->duration_minutes < 60) {
            return $this->duration_minutes . ' minutos';
        }

        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($minutes > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }

        return $hours . ' hora' . ($hours > 1 ? 's' : '');
    }

    /**
     * Check if the service requires equipment.
     */
    public function getRequiresEquipmentAttribute(): bool
    {
        return !empty($this->equipment_required);
    }

    /**
     * Check if the service has prerequisites.
     */
    public function getHasPrerequisitesAttribute(): bool
    {
        return !empty($this->prerequisites);
    }
}
