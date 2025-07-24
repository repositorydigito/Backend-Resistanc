<?php

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

        // nuevo
        'type_document',
        'document_number',
    ];

    protected $casts = [
        // Removed 'specialties' and 'certifications' casts to avoid conflicts with accessors
        'availability_schedule' => 'array',
        'is_head_coach' => 'boolean',
        'experience_years' => 'integer',
        'rating_average' => 'decimal:2',
        'total_classes_taught' => 'integer',
        'hourly_rate_soles' => 'decimal:2',
        'hire_date' => 'date',


    ];

    /**
     * Mutator for specialties to ensure it's always stored as JSON
     */
    public function setSpecialtiesAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['specialties'] = json_encode([]);
            return;
        }

        if (is_string($value)) {
            // If it's already a JSON string, validate it
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['specialties'] = $value;
            } else {
                // If it's not valid JSON, treat as single value
                $this->attributes['specialties'] = json_encode([$value]);
            }
            return;
        }

        if (is_array($value)) {
            $this->attributes['specialties'] = json_encode($value);
            return;
        }

        // Fallback for any other type
        $this->attributes['specialties'] = json_encode([]);
    }

    /**
     * Mutator for certifications to ensure it's always stored as JSON
     */
    public function setCertificationsAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['certifications'] = json_encode([]);
            return;
        }

        if (is_string($value)) {
            // If it's already a JSON string, validate it
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['certifications'] = $value;
            } else {
                // If it's not valid JSON, treat as single value
                $this->attributes['certifications'] = json_encode([$value]);
            }
            return;
        }

        if (is_array($value)) {
            $this->attributes['certifications'] = json_encode($value);
            return;
        }

        // Fallback for any other type
        $this->attributes['certifications'] = json_encode([]);
    }

    /**
     * Accessor for specialties to ensure it's always an array
     */
    public function getSpecialtiesAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * Accessor for certifications to ensure it's always an array
     */
    public function getCertificationsAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

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
    // public function classes(): HasMany
    // {
    //     return $this->hasMany(ClassModel::class, 'instructor_id');
    // }

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
        $certifications = $this->certifications;

        if (empty($certifications)) {
            return '';
        }

        return implode(', ', $certifications);
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
        // Use 'score' field if available, fallback to 'rating'
        $average = $this->ratings()->avg('score') ?? $this->ratings()->avg('rating');
        $this->update(['rating_average' => $average ?? 0]);
    }

    /**
     * Increment the total classes taught.
     */
    public function incrementClassesTaught(): void
    {
        $this->increment('total_classes_taught');
    }


      public function userFavorites(): BelongsToMany
    {
        return $this->morphToMany(UserFavorite::class, 'favoritable', 'user_favorites', 'favoritable_id', 'user_id')
            ->withPivot('notes', 'priority')
            ->withTimestamps();
    }


}
