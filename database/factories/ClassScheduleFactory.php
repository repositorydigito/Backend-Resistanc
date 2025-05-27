<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a random date within the next 30 days
        $scheduleDate = $this->faker->dateTimeBetween('now', '+30 days');
        $carbonDate = Carbon::parse($scheduleDate);

        // Define time slots based on day of week
        $timeSlots = $this->getTimeSlotsForDay($carbonDate->dayOfWeek);
        $selectedSlot = $this->faker->randomElement($timeSlots);

        $startTime = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $selectedSlot['start']);
        $endTime = Carbon::parse($scheduleDate->format('Y-m-d') . ' ' . $selectedSlot['end']);

        // Calculate available spots (usually 80-100% of max capacity)
        $maxCapacity = $this->faker->numberBetween(15, 35);
        $bookedSpots = $this->faker->numberBetween(0, (int)($maxCapacity * 0.8));
        $availableSpots = $maxCapacity - $bookedSpots;

        return [
            'class_id' => ClassModel::factory(),
            'scheduled_date' => $scheduleDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_capacity' => $maxCapacity,
            'booked_spots' => $bookedSpots,
            'available_spots' => $availableSpots,
            'waitlist_count' => $availableSpots <= 0 ? $this->faker->numberBetween(0, 10) : 0,
            'price_per_class' => $this->faker->randomFloat(2, 45.00, 85.00),
            'special_price' => $this->faker->boolean(20) ? $this->faker->randomFloat(2, 35.00, 65.00) : null,
            'instructor_notes' => $this->generateInstructorNotes(),
            'class_notes' => $this->generateClassNotes(),
            'is_cancelled' => $this->faker->boolean(5), // 5% cancelled
            'cancellation_reason' => null,
            'status' => $this->faker->randomElement(['scheduled', 'scheduled', 'scheduled', 'in_progress', 'completed']),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Get time slots based on day of week.
     */
    private function getTimeSlotsForDay(int $dayOfWeek): array
    {
        // Monday = 1, Sunday = 0
        $weekdaySlots = [
            ['start' => '06:00:00', 'end' => '07:00:00'], // Early morning
            ['start' => '07:00:00', 'end' => '08:00:00'], // Morning rush
            ['start' => '08:00:00', 'end' => '09:00:00'], // Late morning
            ['start' => '09:00:00', 'end' => '10:00:00'], // Mid morning
            ['start' => '10:00:00', 'end' => '11:00:00'], // Late morning
            ['start' => '12:00:00', 'end' => '13:00:00'], // Lunch time
            ['start' => '17:00:00', 'end' => '18:00:00'], // After work
            ['start' => '18:00:00', 'end' => '19:00:00'], // Evening prime
            ['start' => '19:00:00', 'end' => '20:00:00'], // Evening
            ['start' => '20:00:00', 'end' => '21:00:00'], // Late evening
        ];

        $weekendSlots = [
            ['start' => '08:00:00', 'end' => '09:00:00'], // Morning
            ['start' => '09:00:00', 'end' => '10:00:00'], // Mid morning
            ['start' => '10:00:00', 'end' => '11:00:00'], // Late morning
            ['start' => '11:00:00', 'end' => '12:00:00'], // Pre-lunch
            ['start' => '12:00:00', 'end' => '13:00:00'], // Lunch
            ['start' => '14:00:00', 'end' => '15:00:00'], // Afternoon
            ['start' => '15:00:00', 'end' => '16:00:00'], // Mid afternoon
            ['start' => '16:00:00', 'end' => '17:00:00'], // Late afternoon
            ['start' => '17:00:00', 'end' => '18:00:00'], // Early evening
        ];

        // Weekend: Saturday = 6, Sunday = 0
        return ($dayOfWeek === 0 || $dayOfWeek === 6) ? $weekendSlots : $weekdaySlots;
    }

    /**
     * Generate instructor notes.
     */
    private function generateInstructorNotes(): ?string
    {
        $notes = [
            'Enfoque en técnica para principiantes',
            'Playlist energética preparada',
            'Incluir modificaciones para lesiones',
            'Recordar hidratación constante',
            'Clase temática: música de los 90s',
            'Trabajar core intensivo',
            'Incluir ejercicios de flexibilidad',
            'Preparar variaciones avanzadas',
            'Enfoque en respiración',
            'Clase de recuperación activa',
        ];

        return $this->faker->boolean(40) ? $this->faker->randomElement($notes) : null;
    }

    /**
     * Generate class notes.
     */
    private function generateClassNotes(): ?string
    {
        $notes = [
            'Traer toalla y botella de agua',
            'Llegar 10 minutos antes',
            'Clase apta para todos los niveles',
            'Se proporcionan todos los equipos',
            'Usar ropa cómoda',
            'Calcetines antideslizantes requeridos',
            'No comer 2 horas antes',
            'Clase con música en vivo',
            'Incluye sesión de estiramiento',
            'Temperatura ambiente controlada',
        ];

        return $this->faker->boolean(30) ? $this->faker->randomElement($notes) : null;
    }

    /**
     * Indicate that the class is fully booked.
     */
    public function fullyBooked(): static
    {
        return $this->state(function (array $attributes) {
            $maxCapacity = $attributes['max_capacity'] ?? 20;
            return [
                'booked_spots' => $maxCapacity,
                'available_spots' => 0,
                'waitlist_count' => $this->faker->numberBetween(1, 8),
            ];
        });
    }

    /**
     * Indicate that the class is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cancelled' => true,
            'status' => 'cancelled',
            'cancellation_reason' => $this->faker->randomElement([
                'Instructor enfermo',
                'Mantenimiento del estudio',
                'Falta de participantes',
                'Emergencia técnica',
                'Evento especial',
            ]),
        ]);
    }

    /**
     * Indicate that the class is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'scheduled_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the class is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'scheduled_date' => now()->subMinutes($this->faker->numberBetween(5, 45)),
        ]);
    }

    /**
     * Create a morning class.
     */
    public function morning(): static
    {
        return $this->state(function (array $attributes) {
            $date = $attributes['scheduled_date'] ?? now()->addDays(1);
            $morningSlots = [
                ['start' => '06:00:00', 'end' => '07:00:00'],
                ['start' => '07:00:00', 'end' => '08:00:00'],
                ['start' => '08:00:00', 'end' => '09:00:00'],
                ['start' => '09:00:00', 'end' => '10:00:00'],
            ];
            
            $slot = $this->faker->randomElement($morningSlots);
            $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['start']);
            $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['end']);

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Create an evening class.
     */
    public function evening(): static
    {
        return $this->state(function (array $attributes) {
            $date = $attributes['scheduled_date'] ?? now()->addDays(1);
            $eveningSlots = [
                ['start' => '17:00:00', 'end' => '18:00:00'],
                ['start' => '18:00:00', 'end' => '19:00:00'],
                ['start' => '19:00:00', 'end' => '20:00:00'],
                ['start' => '20:00:00', 'end' => '21:00:00'],
            ];
            
            $slot = $this->faker->randomElement($eveningSlots);
            $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['start']);
            $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['end']);

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }
}
