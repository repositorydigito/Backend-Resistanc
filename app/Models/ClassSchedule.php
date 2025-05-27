<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'max_capacity',
        'booked_spots',
        'available_spots',
        'waitlist_count',
        'price_per_class',
        'special_price',
        'instructor_notes',
        'class_notes',
        'is_cancelled',
        'cancellation_reason',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'max_capacity' => 'integer',
        'booked_spots' => 'integer',
        'available_spots' => 'integer',
        'waitlist_count' => 'integer',
        'price_per_class' => 'decimal:2',
        'special_price' => 'decimal:2',
        'is_cancelled' => 'boolean',
    ];

    /**
     * Get the class for this schedule.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the bookings for this schedule.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the booking seats for this schedule.
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /**
     * Get the waitlist entries for this schedule.
     */
    public function waitlist(): HasMany
    {
        return $this->hasMany(ClassWaitlist::class);
    }

    /**
     * Scope to get upcoming schedules.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', now());
    }

    /**
     * Scope to get past schedules.
     */
    public function scopePast($query)
    {
        return $query->where('scheduled_date', '<', now());
    }

    /**
     * Scope to get today's schedules.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    /**
     * Scope to get available schedules (not full).
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_spots', '>', 0);
    }

    /**
     * Scope to get cancelled schedules.
     */
    public function scopeCancelled($query)
    {
        return $query->where('is_cancelled', true);
    }

    /**
     * Check if the schedule is full.
     */
    public function getIsFullAttribute(): bool
    {
        return $this->available_spots <= 0;
    }

    /**
     * Check if the schedule has a waitlist.
     */
    public function getHasWaitlistAttribute(): bool
    {
        return $this->waitlist_count > 0;
    }

    /**
     * Get the final price (special price if available).
     */
    public function getFinalPriceAttribute(): float
    {
        return $this->special_price ?? $this->price_per_class;
    }

    /**
     * Check if there's a special price.
     */
    public function getHasSpecialPriceAttribute(): bool
    {
        return $this->special_price !== null && $this->special_price < $this->price_per_class;
    }

    /**
     * Get the occupancy percentage.
     */
    public function getOccupancyPercentageAttribute(): int
    {
        if ($this->max_capacity <= 0) {
            return 0;
        }

        return (int) round(($this->booked_spots / $this->max_capacity) * 100);
    }

    /**
     * Get the time in a human-readable format.
     */
    public function getTimeFormattedAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    /**
     * Get the date in a human-readable format.
     */
    public function getDateFormattedAttribute(): string
    {
        return $this->scheduled_date->format('d/m/Y');
    }

    /**
     * Get the full datetime formatted.
     */
    public function getDatetimeFormattedAttribute(): string
    {
        return $this->scheduled_date->format('d/m/Y') . ' ' . $this->time_formatted;
    }

    /**
     * Check if the schedule is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->end_time->isPast();
    }

    /**
     * Check if the schedule is happening now.
     */
    public function getIsInProgressAttribute(): bool
    {
        $now = now();
        return $now->between($this->start_time, $this->end_time);
    }

    /**
     * Check if booking is still allowed.
     */
    public function getCanBookAttribute(): bool
    {
        return !$this->is_cancelled && 
               !$this->is_past && 
               $this->available_spots > 0 &&
               $this->start_time->diffInHours(now()) >= 2; // At least 2 hours before
    }

    /**
     * Book a spot in this schedule.
     */
    public function bookSpot(): bool
    {
        if (!$this->can_book) {
            return false;
        }

        $this->increment('booked_spots');
        $this->decrement('available_spots');

        return true;
    }

    /**
     * Cancel a booking for this schedule.
     */
    public function cancelBooking(): void
    {
        $this->decrement('booked_spots');
        $this->increment('available_spots');
    }

    /**
     * Add to waitlist.
     */
    public function addToWaitlist(): void
    {
        $this->increment('waitlist_count');
    }

    /**
     * Remove from waitlist.
     */
    public function removeFromWaitlist(): void
    {
        if ($this->waitlist_count > 0) {
            $this->decrement('waitlist_count');
        }
    }

    /**
     * Cancel the schedule.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'is_cancelled' => true,
            'cancellation_reason' => $reason,
            'status' => 'cancelled',
        ]);
    }
}
