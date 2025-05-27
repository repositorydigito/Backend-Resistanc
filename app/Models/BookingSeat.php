<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BookingSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_schedule_id',
        'booking_id',
        'seat_number',
        'seat_row',
        'seat_position',
        'status',
        'equipment_type',
        'equipment_id',
        'special_requirements',
        'assigned_at',
        'released_at',
    ];

    protected $casts = [
        'seat_number' => 'integer',
        'seat_position' => 'integer',
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    /**
     * Get the class schedule for this seat.
     */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * Get the booking for this seat.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scope to get available seats.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get occupied seats.
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    /**
     * Get the seat identifier.
     */
    public function getSeatIdentifierAttribute(): string
    {
        if ($this->seat_row && $this->seat_position) {
            return $this->seat_row . $this->seat_position;
        }

        return (string) $this->seat_number;
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return match ($this->status) {
            'available' => 'Disponible',
            'reserved' => 'Reservado',
            'occupied' => 'Ocupado',
            'maintenance' => 'Mantenimiento',
            default => ucfirst($this->status),
        };
    }

    /**
     * Reserve the seat.
     */
    public function reserve(Booking $booking): void
    {
        $this->update([
            'booking_id' => $booking->id,
            'status' => 'reserved',
            'assigned_at' => now(),
        ]);
    }

    /**
     * Release the seat.
     */
    public function release(): void
    {
        $this->update([
            'booking_id' => null,
            'status' => 'available',
            'released_at' => now(),
        ]);
    }

    /**
     * Mark as occupied.
     */
    public function occupy(): void
    {
        $this->update(['status' => 'occupied']);
    }

    /**
     * Mark for maintenance.
     */
    public function markForMaintenance(): void
    {
        $this->update(['status' => 'maintenance']);
    }
}
