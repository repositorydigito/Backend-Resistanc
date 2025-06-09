<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            $studio->generateSeats();
        });

        // Regenerate seats when studio configuration changes
        static::updated(function ($studio) {
            // Only regenerate if seating configuration changed
            if ($studio->wasChanged(['row', 'column', 'addressing', 'capacity_per_seat'])) {
                $studio->generateSeats();
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
        // Delete existing seats first
        $this->seats()->delete();

        $seatCapacity = (int) $this->capacity_per_seat;
        $rows = (int) $this->row;
        $columns = (int) $this->column;
        $addressing = $this->addressing;

        if ($seatCapacity <= 0 || $rows <= 0 || $columns <= 0) {
            return;
        }

        $seats = [];
        $seatCount = 0;

        // Generate seats row by row, column by column, up to capacity_per_seat
        for ($row = 1; $row <= $rows && $seatCount < $seatCapacity; $row++) {
            for ($col = 1; $col <= $columns && $seatCount < $seatCapacity; $col++) {
                // Calculate the actual column position based on addressing
                $actualColumn = $this->calculateColumnPosition($col, $columns, $addressing);

                $seats[] = [
                    'studio_id' => $this->id,
                    'row' => $row,
                    'column' => $actualColumn,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $seatCount++;
            }
        }

        // Bulk insert for better performance
        if (!empty($seats)) {
            Seat::insert($seats);
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
}
