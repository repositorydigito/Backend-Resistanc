<?php

declare(strict_types=1);

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

        // static::updating(function ($classSchedule) {
        //     $classSchedule->available_spots = $classSchedule->max_capacity - ($classSchedule->booked_spots ?? 0);
        // });
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

    // 🆕 Obtener mapa de asientos con estado
    public function getSeatMap()
    {
        return $this->seatAssignments()
            ->with(['seat' => function ($query) {
                $query->select('id', 'row', 'number', 'type');
            }])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->seats_id,
                    'row' => $assignment->seat->row,
                    'number' => $assignment->seat->number,
                    'type' => $assignment->seat->type,
                    'status' => $assignment->status,
                    'user_id' => $assignment->user_id,
                    'reserved_at' => $assignment->reserved_at,
                    'expires_at' => $assignment->expires_at,
                ];
            });
    }
}
