<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'discipline_id',
        'instructor_id',
        'studio_id',
        'name',
        'description',
        'duration_minutes',
        'max_participants',
        'type',
        'intensity_level',
        'difficulty_level',
        'music_genre',
        'special_requirements',
        'is_featured',
        'status',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the discipline for this class.
     */
    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    /**
     * Get the instructor for this class.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Get the studio where this class is held.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    /**
     * Get the schedules for this class.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    /**
     * Scope to get only active classes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only featured classes.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by discipline.
     */
    public function scopeForDiscipline($query, int $disciplineId)
    {
        return $query->where('discipline_id', $disciplineId);
    }

    /**
     * Scope to filter by instructor.
     */
    public function scopeForInstructor($query, int $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope to filter by intensity level.
     */
    public function scopeByIntensity($query, string $intensity)
    {
        return $query->where('intensity_level', $intensity);
    }

    /**
     * Get the intensity level display name.
     */
    public function getIntensityDisplayNameAttribute(): string
    {
        return match ($this->intensity_level) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            default => ucfirst($this->intensity_level),
        };
    }

    /**
     * Get the difficulty level display name.
     */
    public function getDifficultyDisplayNameAttribute(): string
    {
        return match ($this->difficulty_level) {
            'beginner' => 'Principiante',
            'intermediate' => 'Intermedio',
            'advanced' => 'Avanzado',
            'all_levels' => 'Todos los Niveles',
            default => ucfirst($this->difficulty_level),
        };
    }

    /**
     * Get the type display name.
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return match ($this->type) {
            'regular' => 'Regular',
            'workshop' => 'Taller',
            'private' => 'Privada',
            'special' => 'Especial',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the duration in a human-readable format.
     */
    public function getDurationFormattedAttribute(): string
    {
        $hours = intval($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'min';
        }

        return $minutes . ' minutos';
    }

    /**
     * Check if the class is suitable for beginners.
     */
    public function getIsSuitableForBeginnersAttribute(): bool
    {
        return in_array($this->difficulty_level, ['beginner', 'all_levels']);
    }

    /**
     * Get the next scheduled session for this class.
     */
    public function getNextScheduleAttribute(): ?ClassSchedule
    {
        return $this->schedules()
            ->where('scheduled_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('scheduled_date')
            ->first();
    }
}
