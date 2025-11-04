<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

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

        'booking_opens_at',     // ✅ Asegúrate de que estén aquí
        'booking_closes_at',    // ✅
        'cancellation_deadline', // ✅
        // ✅ FALTABA: Agregar los campos de las relaciones
        'instructor_id',
        'studio_id',
        'substitute_instructor_id', // Nuevo campo para suplente
        'is_replaced', // Nuevo campo para indicar si es un reemplazo


        // Nuevo
        'img_url',
        'theme',

    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($classSchedule) {
            Log::info("=== ClassSchedule::creating EVENT ===");
            Log::info("booking_opens_at antes: " . json_encode($classSchedule->booking_opens_at));
            Log::info("booking_closes_at antes: " . json_encode($classSchedule->booking_closes_at));
            Log::info("cancellation_deadline antes: " . json_encode($classSchedule->cancellation_deadline));

            // Obtener los valores raw directamente de los atributos sin casts
            $rawAttributes = $classSchedule->getAttributes();
            Log::info("Atributos raw: " . json_encode($rawAttributes));

            $classSchedule->available_spots = $classSchedule->max_capacity - ($classSchedule->booked_spots ?? 0);

            // ✅ Configurar límite de cancelación automáticamente si no se proporciona
            if (empty($classSchedule->cancellation_deadline)) {
                // Usar los valores raw directamente desde los atributos
                $scheduledDate = $rawAttributes['scheduled_date'] ?? null;
                $startTime = $rawAttributes['start_time'] ?? null;

                Log::info("Calculando cancellation_deadline - scheduled_date: " . json_encode($scheduledDate) . ", start_time: " . json_encode($startTime));

                if ($scheduledDate && $startTime) {
                    // Asegurar que scheduledDate sea solo fecha
                    if (strlen($scheduledDate) > 10) {
                        $scheduledDate = substr($scheduledDate, 0, 10); // Solo YYYY-MM-DD
                    }

                    // Asegurar que startTime sea solo hora
                    if (strlen($startTime) > 8) {
                        $parts = explode(' ', $startTime);
                        $startTime = end($parts); // Tomar la última parte (la hora)
                    }

                    Log::info("Valores limpiados - scheduled_date: " . json_encode($scheduledDate) . ", start_time: " . json_encode($startTime));

                    $classDate = Carbon::parse($scheduledDate . ' ' . $startTime);
                    $cancellationDeadline = $classDate->copy()->subDays(3);

                    $classSchedule->cancellation_deadline = $cancellationDeadline->format('Y-m-d H:i:s');
                    Log::info("cancellation_deadline calculado: " . $classSchedule->cancellation_deadline);
                }
            }

            // ✅ SOLO configurar fechas de reserva por defecto si NO vienen de la importación
            if (is_null($classSchedule->booking_opens_at)) {
                // Las reservas abren 7 días antes de la clase
                $scheduledDate = $rawAttributes['scheduled_date'] ?? null;
                $startTime = $rawAttributes['start_time'] ?? null;

                if ($scheduledDate && $startTime) {
                    // Limpiar valores como arriba
                    if (strlen($scheduledDate) > 10) {
                        $scheduledDate = substr($scheduledDate, 0, 10);
                    }
                    if (strlen($startTime) > 8) {
                        $parts = explode(' ', $startTime);
                        $startTime = end($parts);
                    }

                    $classDate = Carbon::parse($scheduledDate . ' ' . $startTime);
                    $bookingOpens = $classDate->copy()->subDays(7);

                    $classSchedule->booking_opens_at = $bookingOpens->format('Y-m-d H:i:s');
                }
            }

            if (is_null($classSchedule->booking_closes_at)) {
                // Las reservas cierran 1 hora antes de la clase
                $scheduledDate = $rawAttributes['scheduled_date'] ?? null;
                $startTime = $rawAttributes['start_time'] ?? null;

                if ($scheduledDate && $startTime) {
                    // Limpiar valores como arriba
                    if (strlen($scheduledDate) > 10) {
                        $scheduledDate = substr($scheduledDate, 0, 10);
                    }
                    if (strlen($startTime) > 8) {
                        $parts = explode(' ', $startTime);
                        $startTime = end($parts);
                    }

                    $classDate = Carbon::parse($scheduledDate . ' ' . $startTime);
                    $bookingCloses = $classDate->copy()->subHour();

                    $classSchedule->booking_closes_at = $bookingCloses->format('Y-m-d H:i:s');
                }
            }

            Log::info("=== ClassSchedule::creating EVENT FINAL ===");
            Log::info("booking_opens_at después: " . json_encode($classSchedule->booking_opens_at));
            Log::info("booking_closes_at después: " . json_encode($classSchedule->booking_closes_at));
            Log::info("cancellation_deadline después: " . json_encode($classSchedule->cancellation_deadline));
        });

        // Evento después de crear el horario
        static::created(function ($classSchedule) {
            Log::info("=== ClassSchedule::created EVENT ===");
            Log::info("Generando asientos automáticamente para horario ID: {$classSchedule->id}");

            try {
                $classSchedule->generateSeatsAutomatically();
                Log::info("Asientos generados exitosamente para horario ID: {$classSchedule->id}");
            } catch (\Exception $e) {
                Log::error("Error generando asientos para horario ID: {$classSchedule->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });

        // Evento antes de actualizar el horario
        static::updating(function ($classSchedule) {
            // Actualizar spots disponibles si cambió la capacidad
            if ($classSchedule->isDirty('max_capacity')) {
                $classSchedule->available_spots = $classSchedule->max_capacity - ($classSchedule->booked_spots ?? 0);
            }
        });

        // Evento después de actualizar el horario
        static::updated(function ($classSchedule) {
            // Verificar si cambió la sala
            if ($classSchedule->wasChanged('studio_id')) {
                $oldStudioId = $classSchedule->getOriginal('studio_id');
                $newStudioId = $classSchedule->studio_id;

                Log::info("Cambio de sala detectado en horario", [
                    'schedule_id' => $classSchedule->id,
                    'old_studio_id' => $oldStudioId,
                    'new_studio_id' => $newStudioId
                ]);

                try {
                    // Eliminar TODOS los asientos existentes del horario anterior
                    $deletedSeats = ClassScheduleSeat::where('class_schedules_id', $classSchedule->id)->delete();

                    Log::info("Asientos eliminados del horario anterior", [
                        'schedule_id' => $classSchedule->id,
                        'deleted_seats' => $deletedSeats
                    ]);

                    // Generar nuevos asientos para la nueva sala
                    $seatsGenerated = $classSchedule->generateSeatsAutomatically();

                    Log::info("Asientos regenerados exitosamente", [
                        'schedule_id' => $classSchedule->id,
                        'new_studio_id' => $newStudioId,
                        'seats_generated' => $seatsGenerated
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error regenerando asientos después del cambio de sala", [
                        'schedule_id' => $classSchedule->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Verificar si se canceló la clase directamente (sin usar el método cancel())
            // También verificar si is_cancelled cambió a true
            if (($classSchedule->wasChanged('status') && 
                $classSchedule->status === 'cancelled' && 
                $classSchedule->getOriginal('status') !== 'cancelled') ||
                ($classSchedule->wasChanged('is_cancelled') && 
                $classSchedule->is_cancelled === true &&
                $classSchedule->getOriginal('is_cancelled') !== true)) {
                
                // Asegurarse de que el status también esté en 'cancelled'
                if ($classSchedule->status !== 'cancelled') {
                    $classSchedule->status = 'cancelled';
                    $classSchedule->saveQuietly(); // Guardar sin disparar eventos para evitar loops
                }
                
                // Cancelar automáticamente las reservas de zapatos
                $reason = $classSchedule->cancellation_reason ?? 'Clase cancelada';
                try {
                    $classSchedule->cancelFootwearReservations($reason);
                } catch (\Exception $e) {
                    Log::error('Error cancelando reservas de zapatos desde evento updated', [
                        'class_schedule_id' => $classSchedule->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        });
    }

    protected $casts = [
        'scheduled_date' => 'date', // Solo fecha, sin hora
        // NO usar casts para start_time y end_time - mantenerlos como string
        // 'start_time' => 'datetime',
        // 'end_time' => 'datetime',
        'booking_opens_at' => 'datetime',
        'booking_closes_at' => 'datetime',
        'cancellation_deadline' => 'datetime',
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
    public function class()
    {
        return $this->belongsTo(ClassModel::class); // Ajusta el modelo real si se llama diferente
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
        // Actualizar el estado de la clase
        $this->update([
            'is_cancelled' => true,
            'cancellation_reason' => $reason,
            'status' => 'cancelled',
        ]);

        // Cancelar automáticamente todas las reservas de zapatos asociadas a esta clase
        $this->cancelFootwearReservations($reason);
    }

    /**
     * Cancelar todas las reservas de zapatos asociadas a esta clase.
     */
    public function cancelFootwearReservations(?string $reason = null): int
    {
        // Cargar la relación class si no está cargada
        if (!$this->relationLoaded('class')) {
            $this->load('class');
        }

        // Obtener todas las reservas de zapatos pendientes o confirmadas para esta clase
        // Cargar la relación footwear para obtener información del tamaño
        $footwearReservations = \App\Models\FootwearReservation::where('class_schedules_id', $this->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('footwear')
            ->get();

        if ($footwearReservations->isEmpty()) {
            Log::info('No hay reservas de zapatos para cancelar', [
                'class_schedule_id' => $this->id
            ]);
            return 0;
        }

        // Agrupar por talla ANTES de cancelar para el log
        $canceledBySize = [];
        foreach ($footwearReservations as $reservation) {
            $footwear = $reservation->footwear;
            if ($footwear && $footwear->size) {
                $size = $footwear->size;
                if (!isset($canceledBySize[$size])) {
                    $canceledBySize[$size] = 0;
                }
                $canceledBySize[$size]++;
            }
        }

        // Cancelar todas las reservas
        $canceledCount = \App\Models\FootwearReservation::where('class_schedules_id', $this->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->update(['status' => 'canceled']);

        Log::info('Reservas de zapatos canceladas automáticamente por cancelación de clase', [
            'class_schedule_id' => $this->id,
            'class_name' => $this->class->name ?? 'N/A',
            'scheduled_date' => $this->scheduled_date,
            'start_time' => $this->start_time,
            'cancellation_reason' => $reason,
            'total_reservations_canceled' => $canceledCount,
            'canceled_by_size' => $canceledBySize
        ]);

        return $canceledCount;
    }

    /**
     * ✅ FALTABA: Get the instructor for this schedule.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    /**
     * ✅ FALTABA: Get the studio for this schedule.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }



    // 🆕 Relación many-to-many con asientos
    public function seats(): BelongsToMany
    {
        return $this->belongsToMany(Seat::class, 'class_schedule_seat', 'class_schedules_id', 'seats_id')
            ->withPivot(['user_id', 'status', 'reserved_at', 'expires_at'])
            ->withTimestamps();
    }

    // 🆕 Relación directa con la tabla intermedia
    public function seatAssignments(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class, 'class_schedules_id');
    }

    // 🆕 Asientos disponibles
    public function availableSeats()
    {
        return $this->seatAssignments()->available()->with('seat');
    }

    // 🆕 Asientos reservados
    public function reservedSeats()
    {
        return $this->seatAssignments()->reserved()->with('seat', 'user');
    }

    // 🆕 Asientos ocupados
    public function occupiedSeats()
    {
        return $this->seatAssignments()->occupied()->with('seat', 'user');
    }

    // 🆕 Liberar reservas expiradas
    public function releaseExpiredReservations(): int
    {
        $expired = $this->seatAssignments()->expired()->get();

        foreach ($expired as $seatAssignment) {
            $seatAssignment->release();
        }

        return $expired->count();
    }

    // 🆕 Regenerar asientos manualmente (elimina todos y crea nuevos)
    public function regenerateSeats(): int
    {
        Log::info("Regenerando asientos manualmente", [
            'schedule_id' => $this->id,
            'studio_id' => $this->studio_id
        ]);

        try {
            // Eliminar todos los asientos existentes
            $deletedCount = ClassScheduleSeat::where('class_schedules_id', $this->id)->delete();

            Log::info("Asientos eliminados para regeneración", [
                'schedule_id' => $this->id,
                'deleted_count' => $deletedCount
            ]);

            // Generar nuevos asientos
            $createdCount = $this->generateSeatsAutomatically();

            Log::info("Asientos regenerados exitosamente", [
                'schedule_id' => $this->id,
                'deleted_count' => $deletedCount,
                'created_count' => $createdCount
            ]);

            return $createdCount;
        } catch (\Exception $e) {
            Log::error("Error regenerando asientos", [
                'schedule_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // 🆕 Generar asientos automáticamente para este horario
    public function generateSeatsAutomatically(): int
    {
        // Verificar que el horario tenga un estudio asignado
        if (!$this->studio_id) {
            Log::warning("No se puede generar asientos: horario sin estudio asignado", [
                'schedule_id' => $this->id
            ]);
            return 0;
        }

        // Obtener el estudio
        $studio = $this->studio;
        if (!$studio) {
            Log::warning("No se puede generar asientos: estudio no encontrado", [
                'schedule_id' => $this->id,
                'studio_id' => $this->studio_id
            ]);
            return 0;
        }

        // Verificar que el estudio tenga asientos configurados
        $studioSeats = $studio->seats()->where('is_active', true)->get();
        if ($studioSeats->isEmpty()) {
            Log::info("No hay asientos activos en el estudio, generando asientos del estudio primero", [
                'schedule_id' => $this->id,
                'studio_id' => $studio->id,
                'studio_name' => $studio->name
            ]);

            // Generar asientos del estudio si no existen
            $studio->generateSeats();
            $studioSeats = $studio->seats()->where('is_active', true)->get();

            if ($studioSeats->isEmpty()) {
                Log::warning("No se pudieron generar asientos para el estudio", [
                    'schedule_id' => $this->id,
                    'studio_id' => $studio->id
                ]);
                return 0;
            }
        }

        // 🚨 ELIMINAR TODOS LOS ASIENTOS EXISTENTES ANTES DE GENERAR NUEVOS
        $existingSeats = ClassScheduleSeat::where('class_schedules_id', $this->id)->get();

        if ($existingSeats->isNotEmpty()) {
            Log::info("Eliminando asientos existentes antes de regenerar", [
                'schedule_id' => $this->id,
                'existing_seats_count' => $existingSeats->count()
            ]);

            // Eliminar todos los asientos existentes
            ClassScheduleSeat::where('class_schedules_id', $this->id)->delete();
        }

        // Generar asientos para este horario
        $created = 0;
        foreach ($studioSeats as $seat) {
            try {
                // Generar código único para este asiento
                $code = $this->generateSeatCode($this->id, $seat->id);

                ClassScheduleSeat::create([
                    'class_schedules_id' => $this->id,
                    'seats_id' => $seat->id,
                    'status' => 'available',
                    'code' => $code,
                ]);
                $created++;

                Log::debug("Asiento creado", [
                    'schedule_id' => $this->id,
                    'seat_id' => $seat->id,
                    'code' => $code
                ]);

            } catch (\Exception $e) {
                Log::error("Error creando asiento", [
                    'schedule_id' => $this->id,
                    'seat_id' => $seat->id,
                    'error' => $e->getMessage()
                ]);
                // Continuar con el siguiente asiento
            }
        }

        Log::info("Asientos generados automáticamente", [
            'schedule_id' => $this->id,
            'studio_id' => $studio->id,
            'studio_name' => $studio->name,
            'seats_created' => $created,
            'total_studio_seats' => $studioSeats->count(),
            'existing_seats_before' => $existingSeats->count()
        ]);

        return $created;
    }

    // 🆕 Generar código único para asiento
    private function generateSeatCode(int $scheduleId, int $seatId): string
    {
        return 'SCH-' . $scheduleId . '-SEAT-' . $seatId . '-' . time() . '-' . rand(1000, 9999);
    }

    // 🆕 Obtener mapa de asientos con estado y distribución completa
    public function getSeatMap()
    {
        $studio = $this->studio;
        if (!$studio) {
            return [
                'error' => 'Studio not found',
                'studio_info' => null,
                'seat_grid' => [],
                'seats_by_status' => [],
                'summary' => []
            ];
        }

        $rows = $studio->row ?? 0;
        $columns = $studio->column ?? 0;

        // Información del estudio
        $studioInfo = [
            'id' => $studio->id,
            'name' => $studio->name,
            'rows' => $rows,
            'columns' => $columns,
            'total_positions' => $rows * $columns,
            'addressing' => $studio->addressing ?? 'left_to_right',
            'capacity' => $studio->capacity_per_seat ?? $studio->max_capacity ?? 0
        ];

        // Cargar asientos asignados con relaciones
        $seatAssignments = $this->seatAssignments()
            ->with(['seat', 'user:id,name,email'])
            ->get()
            ->keyBy(function ($assignment) {
                return $assignment->seat->row . '-' . $assignment->seat->column;
            });

        // Crear grid de asientos
        $seatGrid = [];
        for ($row = 1; $row <= $rows; $row++) {
            for ($col = 1; $col <= $columns; $col++) {
                $seatKey = $row . '-' . $col;
                $assignment = $seatAssignments->get($seatKey);

                if ($assignment && $assignment->seat) {
                    $seat = $assignment->seat;
                    $seatGrid[$row][$col] = [
                        'exists' => true,
                        'seat_id' => $seat->id,
                        'assignment_id' => $assignment->id,
                        'seat_number' => $seat->seat_number ?? null,
                        'row' => $seat->row ?? null,
                        'column' => $seat->column ?? null,
                        'status' => $assignment->status,
                        'is_active' => $seat->is_active ?? false,
                        'user' => $assignment->user ? [
                            'id' => $assignment->user->id,
                            'name' => $assignment->user->name,
                            'email' => $assignment->user->email
                        ] : null,
                        'reserved_at' => $assignment->reserved_at?->toISOString(),
                        'expires_at' => $assignment->expires_at?->toISOString(),
                        'is_expired' => $assignment->isExpired()
                    ];
                } else {
                    $seatGrid[$row][$col] = [
                        'exists' => false,
                        'seat_id' => null,
                        'assignment_id' => null,
                        'seat_number' => null,
                        'row' => $row,
                        'column' => $col,
                        'status' => 'empty',
                        'is_active' => false,
                        'user' => null,
                        'reserved_at' => null,
                        'expires_at' => null,
                        'is_expired' => false
                    ];
                }
            }
        }

        // Agrupar asientos por estado
        $seatsByStatus = $seatAssignments->groupBy('status')->map(function ($assignments, $status) {
            return $assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->seat?->id ?? null,
                    'assignment_id' => $assignment->id,
                    'seat_number' => $assignment->seat?->seat_number ?? null,
                    'row' => $assignment->seat?->row ?? null,
                    'column' => $assignment->seat?->column ?? null,
                    'status' => $assignment->status,
                    'user' => $assignment->user ? [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->name,
                        'email' => $assignment->user->email
                    ] : null,
                    'reserved_at' => $assignment->reserved_at?->toISOString(),
                    'expires_at' => $assignment->expires_at?->toISOString(),
                    'is_expired' => $assignment->isExpired()
                ];
            })->values();
        });

        // Resumen estadístico
        $summary = [
            'total_seats' => $seatAssignments->count(),
            'available_count' => $seatAssignments->where('status', 'available')->count(),
            'reserved_count' => $seatAssignments->where('status', 'reserved')->count(),
            'occupied_count' => $seatAssignments->where('status', 'occupied')->count(),
            'completed_count' => $seatAssignments->where('status', 'Completed')->count(),
            'blocked_count' => $seatAssignments->where('status', 'blocked')->count(),
            'expired_count' => $seatAssignments->filter(fn($a) => $a->isExpired())->count(),
            'empty_positions' => ($rows * $columns) - $seatAssignments->count()
        ];

        return [
            'studio_info' => $studioInfo,
            'seat_grid' => $seatGrid,
            'seats_by_status' => $seatsByStatus,
            'summary' => $summary
        ];
    }

    public function drinks()
    {
        return $this->belongsToMany(Drink::class, 'drink_user', 'classschedule_id', 'drink_id')
            ->withTimestamps()
            ->withPivot('id', 'quantity', 'user_id');
    }
    public function classScheduleSeats(): HasMany
    {
        return $this->hasMany(ClassScheduleSeat::class, 'class_schedules_id');
    }

    /**
     * Get the waiting users for this class schedule.
     */
    public function waitingUsers(): HasMany
    {
        return $this->hasMany(WaitingClass::class, 'class_schedules_id')
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->where(function ($query) {
                $query->where('status', 'waiting')
                    ->orWhere('status', 'notified')
                    ->orWhere('status', 'confirmed');
            });
    }

    /**
     * Get all waiting classes entries (without status filter).
     */
    public function waitingClasses(): HasMany
    {
        return $this->hasMany(WaitingClass::class, 'class_schedules_id');
    }
    /**
     * Get the substitute instructor for this schedule.
     */
    public function substituteInstructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'substitute_instructor_id', 'id')
            ->withDefault([
                'name' => 'No Substitute',
                'profile_image' => 'default-substitute.png', // Cambia esto por una imagen
            ]);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function getClassNameAttribute()
    {
        return $this->class?->name ?? 'Sin nombre';
    }
}
