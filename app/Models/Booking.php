<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Booking extends Model
{

    // No utilizado
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_schedule_id',
        'user_package_id',
        'booking_reference',
        'booking_date',
        'status',
        'credits_used',
        'payment_method',
        'amount_paid',
        'booking_notes',
        'special_requests',
        'check_in_time',
        'check_out_time',
        'cancellation_reason',
        'cancelled_at',
        'no_show_fee_applied',
        'has_extra_options',
        'technical_issues',
        'completed_at',
    ];

    protected $casts = [
        'booking_date' => 'datetime',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'credits_used' => 'integer',
        'amount_paid' => 'decimal:2',
        'no_show_fee_applied' => 'boolean',
        'has_extra_options' => 'boolean',
        'technical_issues' => 'array',
    ];

    /**
     * Get the user who made this booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the class schedule for this booking.
     */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Get the user package used for this booking.
     */
    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class);
    }

    /**
     * Get the booking seats for this booking.
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /**
     * Get the extra options for this booking.
     */
    public function extraOptions(): HasMany
    {
        return $this->hasMany(BookingExtraOption::class);
    }

    /**
     * Scope to get confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get cancelled bookings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get completed bookings.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get no-show bookings.
     */
    public function scopeNoShow($query)
    {
        return $query->where('status', 'no_show');
    }

    /**
     * Scope to get recent bookings.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('booking_date', '>=', now()->subDays($days));
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
            'no_show' => 'No Asistió',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if the booking can be cancelled.
     */
    public function getCanCancelAttribute(): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        // Check if it's within the cancellation window
        $cancellationLimit = $this->userPackage?->package?->cancellation_hours_limit ?? 24;
        $cancellationDeadline = $this->classSchedule->start_time->subHours($cancellationLimit);

        return now()->isBefore($cancellationDeadline);
    }

    /**
     * Check if the booking is in the past.
     */
    public function getIsPastAttribute(): bool
    {
        return $this->classSchedule->end_time->isPast();
    }

    /**
     * Check if the user checked in.
     */
    public function getIsCheckedInAttribute(): bool
    {
        return $this->check_in_time !== null;
    }

    /**
     * Check if the user checked out.
     */
    public function getIsCheckedOutAttribute(): bool
    {
        return $this->check_out_time !== null;
    }

    /**
     * Get the payment method display name.
     */
    public function getPaymentMethodDisplayNameAttribute(): string
    {
        return match ($this->payment_method) {
            'credits' => 'Créditos',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'yape' => 'Yape',
            'plin' => 'Plin',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Confirm the booking.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);

        // Book the spot in the schedule
        $this->classSchedule->bookSpot();
    }

    /**
     * Cancel the booking.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // Free up the spot in the schedule
        $this->classSchedule->cancelBooking();

        // Refund credits if applicable
        if ($this->credits_used > 0 && $this->userPackage) {
            $this->userPackage->refundCredits($this->credits_used);
        }
    }

    /**
     * Mark as no-show.
     */
    public function markAsNoShow(bool $applyFee = true): void
    {
        $this->update([
            'status' => 'no_show',
            'no_show_fee_applied' => $applyFee,
        ]);
    }

    /**
     * Complete the booking.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Check in the user.
     */
    public function checkIn(): void
    {
        $this->update(['check_in_time' => now()]);
    }

    /**
     * Check out the user.
     */
    public function checkOut(): void
    {
        $this->update(['check_out_time' => now()]);
    }
}
