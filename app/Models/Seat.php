<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{

    protected $fillable = [
        'studio_id',
        // 'class_schedule_id',
        'row',
        'column',
        'is_active',
    ];

    protected $casts = [
        'row' => 'integer',
        'column' => 'integer',
        'is_active' => 'boolean',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class);
    }

    /**
     * Get the addressing from the studio.
     */
    public function getAddressingAttribute(): string
    {
        return $this->studio->addressing ?? 'left_to_right';
    }

    /**
     * Scope to get only active seats.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive seats.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Validate seat position against studio configuration.
     */
    public function validatePosition(): bool
    {
        if (!$this->studio) {
            return false;
        }

        $studio = $this->studio;

        // Check if row and column are within studio limits
        if ($this->row < 1 || $this->row > $studio->row) {
            return false;
        }

        if ($this->column < 1 || $this->column > $studio->column) {
            return false;
        }

        return true;
    }

    /**
     * Check if this seat position already exists in the studio.
     */
    public function positionExists(): bool
    {
        return static::where('studio_id', $this->studio_id)
            ->where('row', $this->row)
            ->where('column', $this->column)
            ->where('id', '!=', $this->id ?? 0)
            ->exists();
    }

    // public function classSchedule()
    // {
    //     return $this->belongsTo(ClassSchedule::class);
    // }

    // 🆕 Relación many-to-many con horarios de clase
    public function classSchedules(): BelongsToMany
    {
        return $this->belongsToMany(ClassSchedule::class, 'class_schedule_seat', 'seats_id', 'class_schedules_id')
            ->withPivot(['user_id', 'status', 'reserved_at', 'expires_at'])
            ->withTimestamps();
    }

    // 🆕 Relación directa con asignaciones
    public function seatAssignments(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class, 'seats_id');
    }

    // 🆕 Obtener estado para una clase específica
    public function getStatusForClass(int $classScheduleId): ?string
    {
        $assignment = $this->seatAssignments()
            ->where('class_schedules_id', $classScheduleId)
            ->first();

        return $assignment ? $assignment->status : null;
    }
}
