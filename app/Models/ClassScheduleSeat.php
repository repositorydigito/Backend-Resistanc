<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassScheduleSeat extends Model
{
    protected $table = 'class_schedule_seat';

    protected $fillable = [
        'class_schedules_id',
        'seats_id',
        'user_id',
        'status',
        'reserved_at',
        'expires_at'
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // 🔗 Relaciones
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedules_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seats_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 📋 Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', 'reserved');
    }

    // ⏰ Métodos de utilidad
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'reserved';
    }

    public function reserve(int $userId, int $minutesToExpire = 15): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $this->update([
            'user_id' => $userId,
            'status' => 'reserved',
            'reserved_at' => now(),
            'expires_at' => now()->addMinutes($minutesToExpire)
        ]);

        return true;
    }

    public function confirm(): bool
    {
        if ($this->status !== 'reserved') {
            return false;
        }

        $this->update([
            'status' => 'occupied',
            'expires_at' => null
        ]);

        return true;
    }

    public function release(): bool
    {
        $this->update([
            'user_id' => null,
            'status' => 'available',
            'reserved_at' => null,
            'expires_at' => null
        ]);

        return true;
    }
}
