<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

final class Studio extends Model
{
    use HasFactory;

    /**
     * Boot the model and add event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate seats automatically when a studio is created
        static::created(function ($studio) {
            Log::info("=== Studio::created EVENT TRIGGERED ===");
            Log::info("Studio data:", [
                'id' => $studio->id,
                'name' => $studio->name,
                'row' => $studio->row,
                'column' => $studio->column,
                'capacity_per_seat' => $studio->capacity_per_seat,
                'addressing' => $studio->addressing,
                'all_attributes' => $studio->getAttributes()
            ]);

            try {
                $studio->generateSeats();
                $seatsCount = $studio->seats()->count();
                Log::info("Asientos generados exitosamente para estudio ID: {$studio->id}, Total: {$seatsCount}");
            } catch (\Exception $e) {
                Log::error("Error generando asientos para estudio ID: {$studio->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });

        // Regenerate seats when studio configuration changes
        static::updated(function ($studio) {
            // Only regenerate if seating configuration changed
            if ($studio->wasChanged(['row', 'column', 'addressing', 'capacity_per_seat'])) {
                Log::info("=== Studio::updated EVENT ===");
                Log::info("Regenerando asientos para estudio ID: {$studio->id} debido a cambios en configuración");

                try {
                    $studio->generateSeats();
                    $seatsCount = $studio->seats()->count();
                    Log::info("Asientos regenerados exitosamente para estudio ID: {$studio->id}, Total: {$seatsCount}");
                } catch (\Exception $e) {
                    Log::error("Error regenerando asientos para estudio ID: {$studio->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'location',
        'max_capacity',
        'equipment_available',
        'amenities',
        'studio_type',
        'is_active',
        'capacity_per_seat',
        'addressing',
        'row',
        'column',
    ];

    protected $casts = [
        'equipment_available' => 'array',
        'amenities' => 'array',
        'max_capacity' => 'integer',
        'capacity_per_seat' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the classes held in this studio.
     */
    // public function classes(): HasMany
    // {
    //     return $this->hasMany(ClassModel::class, 'studio_id');
    // }

    /**
     * Get the class schedules for this studio.
     */
    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class, 'studio_id');
    }

    /**
     * Scope to get only active studios.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by studio type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('studio_type', $type);
    }

    /**
     * Get the equipment available as a formatted string.
     */
    public function getEquipmentAvailableStringAttribute(): string
    {
        if (!$this->equipment_available) {
            return '';
        }

        return implode(', ', $this->equipment_available);
    }

    /**
     * Get the amenities as a formatted string.
     */
    public function getAmenitiesStringAttribute(): string
    {
        if (!$this->amenities) {
            return '';
        }

        return implode(', ', $this->amenities);
    }

    /**
     * Check if studio is suitable for a specific discipline.
     */
    public function isSuitableForDiscipline(string $disciplineName): bool
    {
        return match ($disciplineName) {
            'cycling' => $this->studio_type === 'cycling',
            'solidreformer' => $this->studio_type === 'reformer',
            'pilates_mat', 'yoga', 'barre' => in_array($this->studio_type, ['mat', 'multipurpose']),
            default => $this->studio_type === 'multipurpose',
        };
    }

    /**
     * Get the seats in this studio.
     */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    /**
     * Generate seats automatically based on studio configuration.
     * Creates seats up to capacity_per_seat, distributed across rows and columns.
     */
    public function generateSeats(): void
    {
        Log::info("Iniciando generación de asientos para estudio: {$this->name} (ID: {$this->id})");

        // Eliminar los asientos existentes primero
        $this->seats()->delete();

        $seatCapacity = (int) $this->capacity_per_seat;
        $rows = (int) $this->row;
        $columns = (int) $this->column;
        $addressing = $this->addressing;

        if ($seatCapacity <= 0 || $rows <= 0 || $columns <= 0) {
            Log::warning("Configuración inválida para generar asientos", [
                'capacity_per_seat' => $seatCapacity,
                'rows' => $rows,
                'columns' => $columns
            ]);
            return;
        }

        $seats = [];
        $seatNumber = 1;
        for ($row = 1; $row <= $rows && $seatNumber <= $seatCapacity; $row++) {
            for ($col = 1; $col <= $columns && $seatNumber <= $seatCapacity; $col++) {
                $seats[] = [
                    'studio_id'   => $this->id,
                    'row'         => $row,
                    'column'      => $col,
                    'seat_number' => $seatNumber,
                    'is_active'   => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
                $seatNumber++;
            }
        }

        if (!empty($seats)) {
            Seat::insert($seats);
            Log::info("Asientos generados exitosamente", ['total_seats' => count($seats)]);

            // Reordenar los números para asegurar secuencia correlativa
            $this->reorderSeatNumbers();
            Log::info("Números de asientos reordenados después de la generación");
        }
    }


    /**
     * Calculate the actual column position based on addressing direction.
     */
    private function calculateColumnPosition(int $col, int $totalColumns, string $addressing): int
    {
        return match ($addressing) {
            'right_to_left' => $totalColumns - $col + 1,
            'center' => $this->calculateCenterPosition($col, $totalColumns),
            'left_to_right' => $col,
            default => $col,
        };
    }

    /**
     * Calculate center-based positioning.
     * For center addressing, seats are numbered from the center outward.
     * Example with 5 columns: 3,2,4,1,5 (center first, then alternate left/right)
     * Example with 6 columns: 3,4,2,5,1,6 (center-left, center-right, then alternate)
     */
    private function calculateCenterPosition(int $col, int $totalColumns): int
    {
        $isEven = $totalColumns % 2 === 0;

        if ($isEven) {
            // Even number of columns
            $centerLeft = $totalColumns / 2;
            $centerRight = $centerLeft + 1;

            // Create the sequence: center-left, center-right, then alternate outward
            $sequence = [$centerLeft, $centerRight];

            // Add alternating positions outward
            for ($i = 1; $i <= $centerLeft - 1; $i++) {
                $sequence[] = $centerLeft - $i;  // Left side
                $sequence[] = $centerRight + $i; // Right side
            }

            return $sequence[$col - 1] ?? $col;
        } else {
            // Odd number of columns
            $center = (int) ceil($totalColumns / 2);

            // Create the sequence: center, then alternate left/right
            $sequence = [$center];

            // Add alternating positions outward
            for ($i = 1; $i <= ($totalColumns - 1) / 2; $i++) {
                $sequence[] = $center - $i; // Left side
                $sequence[] = $center + $i; // Right side
            }

            return $sequence[$col - 1] ?? $col;
        }
    }

    /**
     * Get a preview of how seats will be distributed for debugging.
     * This method helps visualize the addressing pattern.
     */
    public function getAddressingPreview(): array
    {
        $columns = (int) $this->column;
        $addressing = $this->addressing ?? 'left_to_right';

        if ($columns <= 0) {
            return [];
        }

        $preview = [];
        for ($col = 1; $col <= $columns; $col++) {
            $actualColumn = $this->calculateColumnPosition($col, $columns, $addressing);
            $preview[] = [
                'order' => $col,
                'position' => $actualColumn,
                'label' => "Asiento {$col} → Columna {$actualColumn}"
            ];
        }

        return $preview;
    }

    /**
     * Check if studio has associated class schedules.
     */
    public function hasClassSchedules(): bool
    {
        return $this->classSchedules()->exists();
    }

    /**
     * Get the number of associated class schedules.
     */
    public function getClassSchedulesCountAttribute(): int
    {
        return $this->classSchedules()->count();
    }

    /**
     * Reorder seat numbers to maintain sequential order.
     * This method ensures that seat numbers are always sequential starting from 1.
     */
    public function reorderSeatNumbers(): void
    {
        Log::info("Reordenando números de asientos para estudio: {$this->name} (ID: {$this->id})");

        // Get all seats ordered by row and column
        $seats = $this->seats()
            ->orderBy('row')
            ->orderBy('column')
            ->get();

        if ($seats->isEmpty()) {
            Log::warning("No hay asientos para reordenar en el estudio: {$this->name}");
            return;
        }

        $newSeatNumber = 1;
        $updatedSeats = [];
        $nullSeats = 0;

        foreach ($seats as $seat) {
            $currentSeatNumber = $seat->seat_number;
            
            // Update if seat_number is null or different from expected
            if ($currentSeatNumber === null || $currentSeatNumber !== $newSeatNumber) {
                if ($currentSeatNumber === null) {
                    $nullSeats++;
                }
                $updatedSeats[] = [
                    'id' => $seat->id,
                    'seat_number' => $newSeatNumber,
                    'previous_seat_number' => $currentSeatNumber,
                    'row' => $seat->row,
                    'column' => $seat->column
                ];
            }
            $newSeatNumber++;
        }

        // Update seat numbers in batches to avoid conflicts
        if (!empty($updatedSeats)) {
            foreach ($updatedSeats as $update) {
                Seat::where('id', $update['id'])->update(['seat_number' => $update['seat_number']]);
            }

            Log::info("Números de asientos reordenados", [
                'studio_id' => $this->id,
                'studio_name' => $this->name,
                'total_seats' => $seats->count(),
                'updated_seats' => count($updatedSeats),
                'null_seats_fixed' => $nullSeats,
                'seat_numbers_assigned' => $newSeatNumber - 1
            ]);
        } else {
            Log::info("Todos los asientos ya tienen números correctos", [
                'studio_id' => $this->id,
                'total_seats' => $seats->count()
            ]);
        }
    }

    /**
     * Add a new seat and reorder all seat numbers.
     */
    public function addSeat(int $row, int $column): Seat
    {
        Log::info("Agregando nuevo asiento para estudio: {$this->name}", [
            'row' => $row,
            'column' => $column
        ]);

        // Create the new seat
        $seat = $this->seats()->create([
            'row' => $row,
            'column' => $column,
            'seat_number' => 0, // Temporary number
            'is_active' => true
        ]);

        // Reorder all seat numbers to maintain sequence
        $this->reorderSeatNumbers();

        // Refresh the seat to get the correct seat_number
        $seat->refresh();

        Log::info("Nuevo asiento creado y números reordenados", [
            'seat_id' => $seat->id,
            'final_seat_number' => $seat->seat_number
        ]);

        return $seat;
    }

    /**
     * Delete a seat and reorder remaining seat numbers.
     */
    public function deleteSeat(int $seatId): bool
    {
        $seat = $this->seats()->find($seatId);

        if (!$seat) {
            Log::warning("Asiento no encontrado para eliminar", ['seat_id' => $seatId]);
            return false;
        }

        Log::info("Eliminando asiento", [
            'seat_id' => $seatId,
            'seat_number' => $seat->seat_number,
            'row' => $seat->row,
            'column' => $seat->column
        ]);

        // Check if seat is assigned to any class schedule
        if ($seat->seatAssignments()->exists()) {
            Log::warning("No se puede eliminar asiento asignado a horarios", ['seat_id' => $seatId]);
            return false;
        }

        // Delete the seat
        $deleted = $seat->delete();

        if ($deleted) {
            // Reorder remaining seat numbers
            $this->reorderSeatNumbers();
            Log::info("Asiento eliminado y números reordenados", ['seat_id' => $seatId]);
        }

        return $deleted;
    }
}
