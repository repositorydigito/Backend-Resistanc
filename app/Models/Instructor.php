<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'specialties',
        'bio',
        'certifications',
        'profile_image',
        'instagram_handle',
        'is_head_coach',
        'experience_years',
        'rating_average',
        'total_classes_taught',
        'hire_date',
        'hourly_rate_soles',
        'status',
        'availability_schedule',
    ];

    protected $casts = [
        'specialties' => 'array',
        'certifications' => 'array',
        'availability_schedule' => 'array',
        'is_head_coach' => 'boolean',
        'experience_years' => 'integer',
        'rating_average' => 'decimal:2',
        'total_classes_taught' => 'integer',
        'hourly_rate_soles' => 'decimal:2',
        'hire_date' => 'date',
    ];

    /**
     * Get the user account associated with this instructor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the disciplines this instructor teaches.
     */
    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Discipline::class, 'instructor_discipline');
    }

    /**
     * Get the classes taught by this instructor.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'instructor_id');
    }

    /**
     * Get the class schedules for this instructor.
     */
    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'instructor_id');
    }

    /**
     * Get the ratings for this instructor.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(CoachRating::class, 'instructor_id');
    }

    /**
     * Scope to get only active instructors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only head coaches.
     */
    public function scopeHeadCoaches($query)
    {
        return $query->where('is_head_coach', true);
    }

    /**
     * Scope to filter by discipline.
     */
    public function scopeForDiscipline($query, int $disciplineId)
    {
        return $query->whereHas('disciplines', function ($q) use ($disciplineId) {
            $q->where('discipline_id', $disciplineId);
        });
    }

    /**
     * Get the certifications as a formatted string.
     */
    public function getCertificationsStringAttribute(): string
    {
        if (!$this->certifications) {
            return '';
        }

        return implode(', ', $this->certifications);
    }

    /**
     * Get the specialties as discipline names.
     */
    public function getSpecialtyNamesAttribute(): array
    {
        if (!$this->specialties) {
            return [];
        }

        return $this->disciplines()->pluck('display_name')->toArray();
    }

    /**
     * Check if instructor is available on a specific day and time.
     */
    public function isAvailableAt(\DateTime $dateTime): bool
    {
        if (!$this->availability_schedule) {
            return false;
        }

        $dayName = strtolower($dateTime->format('l'));
        $daySchedule = $this->availability_schedule[$dayName] ?? null;

        if (!$daySchedule || !($daySchedule['available'] ?? false)) {
            return false;
        }

        $time = $dateTime->format('H:i');
        $shifts = $daySchedule['shifts'] ?? [];

        foreach ($shifts as $shift) {
            if ($time >= $shift['start'] && $time <= $shift['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the instructor's rating average.
     */
    public function updateRatingAverage(): void
    {
        $average = $this->ratings()->avg('rating');
        $this->update(['rating_average' => $average ?? 0]);
    }

    /**
     * Increment the total classes taught.
     */
    public function incrementClassesTaught(): void
    {
        $this->increment('total_classes_taught');
    }
}
