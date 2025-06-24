<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassScheduleSeat extends Model
{
    protected $table = 'class_schedule_seat';

    protected $fillable = [

        'status',
        'reserved_at',
        'expires_at',
        'code',

        // relaciones
        'class_schedules_id',
        'user_package_id',
        'seats_id',
        'user_id',

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

    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class, 'user_package_id');
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

    public function reserve(int $userId, int $minutesToExpire = 15, ?int $userPackageId = null, ?string $classStartTime = null): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        // Si se proporciona la fecha de inicio de la clase, calcular expiración basada en eso
        if ($classStartTime) {
            $classDateTime = \Carbon\Carbon::parse($classStartTime);
            $expiresAt = $classDateTime->copy()->addMinutes(10); // 10 minutos después del inicio
        } else {
            // Fallback al comportamiento anterior
            $expiresAt = now()->addMinutes($minutesToExpire);
        }

        $this->update([
            'user_id' => $userId,
            'status' => 'reserved',
            'reserved_at' => now(),
            'expires_at' => $expiresAt,
            'user_package_id' => $userPackageId
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
            'expires_at' => null,
            'user_package_id' => null
        ]);

        return true;
    }

    public function block(): bool
    {
        $this->update([
            'status' => 'blocked',
            'expires_at' => null
        ]);

        return true;
    }

    public function unblock(): bool
    {
        $this->update([
            'user_id' => null,
            'status' => 'available',
            'reserved_at' => null,
            'expires_at' => null
        ]);

        return true;
    }

    // 🔍 Verificar si este asiento pertenece a una sala específica
    public function belongsToStudio(int $studioId): bool
    {
        return $this->seat && $this->seat->studio_id === $studioId;
    }

    // 🔍 Verificar si este asiento está en una posición válida para la sala
    public function isValidForStudio(): bool
    {
        if (!$this->seat || !$this->seat->studio) {
            return false;
        }

        $studio = $this->seat->studio;
        return $this->seat->row <= $studio->row && $this->seat->column <= $studio->column;
    }

    // 🔄 Regenerar código único para este asiento
    public function regenerateCode(): void
    {
        $this->update([
            'code' => $this->generateScheduleSeatCode($this->class_schedules_id, $this->seats_id)
        ]);
    }

    function generateScheduleSeatCode(int $scheduleId, int $seatId): string
    {
        return 'SCH-' . $scheduleId . '-SEAT-' . $seatId . '-' . time() . '-' . rand(1000, 9999);
    }


}
