<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CoachRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instructor_id',
        'booking_id',
        'rating',
        'review',
        'aspects_rated',
        'is_anonymous',
        'is_verified',
        'helpful_votes',
        'reported_count',
        'status',
        'moderated_at',
        'moderator_notes',

        // nuevo
        'score',
    ];

    protected $casts = [
        'rating' => 'integer',
        'score' => 'integer',
        'aspects_rated' => 'array',
        'is_anonymous' => 'boolean',
        'is_verified' => 'boolean',
        'helpful_votes' => 'integer',
        'reported_count' => 'integer',
        'moderated_at' => 'datetime',
    ];

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Update instructor rating average when a rating is created or updated
        static::created(function ($rating) {
            if ($rating->instructor) {
                $rating->instructor->updateRatingAverage();
            }
        });

        static::updated(function ($rating) {
            if ($rating->instructor) {
                $rating->instructor->updateRatingAverage();
            }
        });

        static::deleted(function ($rating) {
            if ($rating->instructor) {
                $rating->instructor->updateRatingAverage();
            }
        });
    }

    /**
     * Get the user who made this rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the instructor being rated.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * Get the booking this rating is for.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope to get approved ratings.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get verified ratings.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get ratings with reviews.
     */
    public function scopeWithReview($query)
    {
        return $query->whereNotNull('review');
    }

    /**
     * Get the rating display (stars).
     */
    public function getRatingStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            'hidden' => 'Oculto',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if the rating is positive (4-5 stars).
     */
    public function getIsPositiveAttribute(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if the rating is negative (1-2 stars).
     */
    public function getIsNegativeAttribute(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Get the user display name (considering anonymity).
     */
    public function getUserDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Usuario Anónimo';
        }

        return $this->user->name ?? 'Usuario';
    }

    /**
     * Approve the rating.
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'moderated_at' => now(),
        ]);

        // Update instructor's rating average
        $this->instructor->updateRatingAverage();
    }

    /**
     * Reject the rating.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'moderated_at' => now(),
            'moderator_notes' => $reason,
        ]);
    }

    /**
     * Mark as helpful.
     */
    public function markAsHelpful(): void
    {
        $this->increment('helpful_votes');
    }

    /**
     * Report the rating.
     */
    public function report(): void
    {
        $this->increment('reported_count');

        // Auto-hide if too many reports
        if ($this->reported_count >= 5) {
            $this->update(['status' => 'hidden']);
        }
    }
}
