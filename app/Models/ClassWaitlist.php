<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ClassWaitlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_schedule_id',
        'position',
        'joined_at',
        'notified_at',
        'expires_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'position' => 'integer',
        'joined_at' => 'datetime',
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user on the waitlist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the class schedule for this waitlist entry.
     */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Scope to get active waitlist entries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get notified entries.
     */
    public function scopeNotified($query)
    {
        return $query->whereNotNull('notified_at');
    }

    /**
     * Scope to get expired entries.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Activo',
            'notified' => 'Notificado',
            'converted' => 'Convertido',
            'expired' => 'Expirado',
            'cancelled' => 'Cancelado',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if the entry is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Notify the user about availability.
     */
    public function notify(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
            'expires_at' => now()->addHours(2), // 2 hours to respond
        ]);
    }

    /**
     * Convert to booking.
     */
    public function convertToBooking(): void
    {
        $this->update(['status' => 'converted']);
    }

    /**
     * Cancel the waitlist entry.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark as expired.
     */
    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }
}
