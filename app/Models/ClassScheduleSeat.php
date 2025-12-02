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
        'user_membership_id',
        'seats_id',
        'user_id',
        'user_waiting_id',


    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Cuando se actualiza el status a 'Completed', actualizar el contador del usuario y crear puntos
        static::updated(function ($seat) {
            if ($seat->wasChanged('status') && $seat->status === 'Completed' && $seat->user_id) {
                // Verificar que no estaba ya en 'Completed' antes
                $oldStatus = $seat->getOriginal('status');
                if ($oldStatus !== 'Completed') {
                    // Incrementar las clases efectivas completadas del usuario
                    $user = \App\Models\User::find($seat->user_id);
                    if ($user) {
                        $user->incrementEffectiveCompletedClasses(1);
                        
                        // Crear registro de puntos para el usuario
                        $seat->createUserPoints($user);
                    }
                }
            }
        });
    }

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

    public function userWaiting(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class, 'user_package_id');
    }

    public function userMembership(): BelongsTo
    {
        return $this->belongsTo(UserMembership::class, 'user_membership_id');
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
        // Si tenía un paquete asignado, devolver la clase
        if ($this->user_package_id) {
            $userPackage = \App\Models\UserPackage::find($this->user_package_id);
            if ($userPackage && $userPackage->user_id === $this->user_id) {
                $userPackage->refundClasses(1);
            }
        }

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

    /**
     * Crear puntos para el usuario cuando completa una clase
     */
    protected function createUserPoints(User $user): void
    {
        try {
            // Obtener la configuración de la compañía
            $company = \App\Models\Company::first();
            if (!$company) {
                \Illuminate\Support\Facades\Log::warning('No se encontró configuración de compañía para crear puntos');
                return;
            }

            // Obtener los meses de duración de los puntos (por defecto 8 meses)
            $monthsPoints = $company->months_points ?? 8;

            // Calcular la fecha de expiración de los puntos
            $dateExpire = now()->addMonths($monthsPoints);

            // Obtener información del paquete si se usó uno
            $packageId = null;
            if ($this->user_package_id) {
                $userPackage = \App\Models\UserPackage::find($this->user_package_id);
                if ($userPackage) {
                    $packageId = $userPackage->package_id;
                }
            }

            // Obtener la membresía asociada al asiento si se usó una, o buscar una activa del usuario
            // Esta es la membresía CON LA QUE SE GANARON los puntos
            $membershipIdEarned = null;
            if ($this->user_membership_id) {
                // Si el asiento tiene una membresía específica asociada, usar esa
                $userMembership = \App\Models\UserMembership::find($this->user_membership_id);
                if ($userMembership) {
                    $membershipIdEarned = $userMembership->membership_id;
                }
            } else {
                // Si no hay membresía específica en el asiento, buscar la membresía activa del usuario con mayor nivel
                $activeMembership = $user->userMemberships()
                    ->with('membership')
                    ->where('status', 'active')
                    ->where('expiry_date', '>', now())
                    ->get()
                    ->sortByDesc(function ($um) {
                        return $um->membership->level ?? 0;
                    })
                    ->first();
                    
                if ($activeMembership) {
                    $membershipIdEarned = $activeMembership->membership_id;
                }
            }

            // Si no hay membresía, intentar determinar basándose en las clases completadas del usuario
            if (!$membershipIdEarned) {
                $user->refresh();
                $effectiveClasses = $user->effective_completed_classes ?? 0;
                
                // Obtener todas las membresías ordenadas por nivel
                $allMemberships = \App\Models\Membership::where('is_active', true)
                    ->orderBy('level', 'asc')
                    ->get();
                
                // Determinar la membresía actual basada en clases completadas
                foreach ($allMemberships as $m) {
                    if ($effectiveClasses >= $m->class_completed) {
                        $membershipIdEarned = $m->id;
                    } else {
                        break;
                    }
                }
            }

            // Determinar la siguiente membresía para active_membership_id
            // Los puntos se usan para la siguiente membresía porque los de la actual ya fueron usados
            $nextMembershipId = null;
            if ($membershipIdEarned) {
                $currentMembership = \App\Models\Membership::find($membershipIdEarned);
                if ($currentMembership) {
                    // Buscar la siguiente membresía con mayor nivel
                    $nextMembership = \App\Models\Membership::where('is_active', true)
                        ->where('level', '>', $currentMembership->level)
                        ->orderBy('level', 'asc')
                        ->first();
                    
                    if ($nextMembership) {
                        $nextMembershipId = $nextMembership->id;
                    }
                }
            }

            // Si no hay siguiente membresía, usar la misma con la que se ganaron
            // (esto significa que el usuario está en la membresía más alta)
            $activeMembershipId = $nextMembershipId ?? $membershipIdEarned;

            // Obtener el user_package_id si se usó un paquete
            $userPackageId = $this->user_package_id ?? null;

            // Crear el registro de puntos
            // IMPORTANTE: 1 asiento completado = 1 punto = 1 clase completada
            // Si un usuario reservó 20 asientos en una clase y todos se completan,
            // se crearán 20 registros de puntos (uno por cada asiento)
            \App\Models\UserPoint::create([
                'user_id' => $user->id,
                'quantity_point' => 1, // 1 punto por asiento completado (cada asiento = 1 clase completada)
                'date_expire' => $dateExpire,
                'membresia_id' => $membershipIdEarned, // Membresía con la que se ganaron los puntos
                'active_membership_id' => $activeMembershipId, // Membresía activa (siguiente membresía si existe, sino la misma)
                'package_id' => $packageId, // Paquete consumido
                'user_package_id' => $userPackageId, // Paquete específico del usuario consumido
            ]);

            \Illuminate\Support\Facades\Log::info('Puntos creados para el usuario', [
                'user_id' => $user->id,
                'class_schedule_seat_id' => $this->id,
                'quantity_point' => 1,
                'date_expire' => $dateExpire->toDateString(),
                'membresia_id' => $membershipIdEarned,
                'active_membership_id' => $activeMembershipId,
                'package_id' => $packageId,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al crear puntos para el usuario', [
                'user_id' => $user->id,
                'class_schedule_seat_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
