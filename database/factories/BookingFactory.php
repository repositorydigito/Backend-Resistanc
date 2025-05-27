<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ClassSchedule;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookingDate = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $status = $this->faker->randomElement(['confirmed', 'confirmed', 'confirmed', 'pending', 'cancelled', 'completed', 'no_show']);
        
        // Generate booking reference
        $bookingReference = 'RST-' . strtoupper($this->faker->bothify('??##??##'));

        // Calculate credits used (usually 1, sometimes 2 for premium classes)
        $creditsUsed = $this->faker->randomElement([1, 1, 1, 1, 2]);

        // Generate payment details
        $paymentMethod = $this->faker->randomElement(['credits', 'cash', 'card', 'transfer']);
        $amountPaid = $paymentMethod === 'credits' ? 0 : $this->faker->randomFloat(2, 45.00, 85.00);

        return [
            'user_id' => User::factory(),
            'class_schedule_id' => ClassSchedule::factory(),
            'user_package_id' => $this->faker->boolean(80) ? UserPackage::factory() : null, // 80% use packages
            'booking_reference' => $bookingReference,
            'booking_date' => $bookingDate,
            'status' => $status,
            'credits_used' => $creditsUsed,
            'payment_method' => $paymentMethod,
            'amount_paid' => $amountPaid,
            'booking_notes' => $this->generateBookingNotes(),
            'special_requests' => $this->generateSpecialRequests(),
            'check_in_time' => $this->generateCheckInTime($status, $bookingDate),
            'check_out_time' => $this->generateCheckOutTime($status, $bookingDate),
            'cancellation_reason' => $status === 'cancelled' ? $this->generateCancellationReason() : null,
            'cancelled_at' => $status === 'cancelled' ? $this->faker->dateTimeBetween($bookingDate, 'now') : null,
            'no_show_fee_applied' => $status === 'no_show' ? $this->faker->boolean(70) : false,
            'has_extra_options' => $this->faker->boolean(20), // 20% have extra options
            'technical_issues' => $this->generateTechnicalIssues(),
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween($bookingDate, 'now') : null,
            'created_at' => $this->faker->dateTimeBetween('-60 days', $bookingDate),
            'updated_at' => $this->faker->dateTimeBetween($bookingDate, 'now'),
        ];
    }

    /**
     * Generate booking notes.
     */
    private function generateBookingNotes(): ?string
    {
        $notes = [
            'Primera vez en esta clase',
            'Cliente regular',
            'Prefiere posición cerca del instructor',
            'Tiene lesión en rodilla - modificaciones',
            'Cliente VIP',
            'Cumpleaños - clase especial',
            'Viene con amiga',
            'Necesita equipamiento especial',
            'Cliente embarazada - cuidados especiales',
            'Reserva de último minuto',
        ];

        return $this->faker->boolean(30) ? $this->faker->randomElement($notes) : null;
    }

    /**
     * Generate special requests.
     */
    private function generateSpecialRequests(): ?string
    {
        $requests = [
            'Bicicleta cerca de la ventana',
            'Reformer en primera fila',
            'Mat en la parte trasera',
            'Equipamiento sanitizado extra',
            'Toalla adicional',
            'Botella de agua fría',
            'Música más baja',
            'Ventilación adicional',
            'Asistencia para ajustar equipo',
            'Recordatorio de hidratación',
        ];

        return $this->faker->boolean(15) ? $this->faker->randomElement($requests) : null;
    }

    /**
     * Generate check-in time based on status.
     */
    private function generateCheckInTime(string $status, $bookingDate): ?Carbon
    {
        if (in_array($status, ['completed', 'no_show']) || $this->faker->boolean(70)) {
            // Check in 5-15 minutes before class
            return Carbon::parse($bookingDate)->subMinutes($this->faker->numberBetween(5, 15));
        }

        return null;
    }

    /**
     * Generate check-out time based on status.
     */
    private function generateCheckOutTime(string $status, $bookingDate): ?Carbon
    {
        if ($status === 'completed' || $this->faker->boolean(60)) {
            // Check out 0-10 minutes after class ends
            return Carbon::parse($bookingDate)->addHour()->addMinutes($this->faker->numberBetween(0, 10));
        }

        return null;
    }

    /**
     * Generate cancellation reason.
     */
    private function generateCancellationReason(): string
    {
        return $this->faker->randomElement([
            'Emergencia personal',
            'Enfermedad',
            'Conflicto de horario',
            'Transporte',
            'Trabajo urgente',
            'Problema familiar',
            'Clima adverso',
            'Lesión',
            'Cambio de planes',
            'No especificado',
        ]);
    }

    /**
     * Generate technical issues.
     */
    private function generateTechnicalIssues(): ?array
    {
        if ($this->faker->boolean(10)) { // 10% have technical issues
            return [
                'issue_type' => $this->faker->randomElement(['equipment', 'audio', 'lighting', 'temperature', 'app']),
                'description' => $this->faker->randomElement([
                    'Problema con bicicleta #12',
                    'Audio intermitente',
                    'Luces muy brillantes',
                    'Temperatura muy alta',
                    'App no carga reserva',
                ]),
                'resolved' => $this->faker->boolean(80),
                'reported_at' => now()->toISOString(),
            ];
        }

        return null;
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancellation_reason' => $this->generateCancellationReason(),
            'cancelled_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the booking is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $bookingDate = $attributes['booking_date'] ?? now()->subDays(1);
            return [
                'status' => 'completed',
                'check_in_time' => Carbon::parse($bookingDate)->subMinutes(10),
                'check_out_time' => Carbon::parse($bookingDate)->addHour()->addMinutes(5),
                'completed_at' => Carbon::parse($bookingDate)->addHour(),
            ];
        });
    }

    /**
     * Indicate that the booking is a no-show.
     */
    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
            'no_show_fee_applied' => true,
            'check_in_time' => null,
            'check_out_time' => null,
        ]);
    }

    /**
     * Indicate that the booking uses credits.
     */
    public function withCredits(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credits',
            'amount_paid' => 0,
            'credits_used' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate that the booking is paid with cash.
     */
    public function withCash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'amount_paid' => $this->faker->randomFloat(2, 45.00, 85.00),
            'credits_used' => 0,
        ]);
    }

    /**
     * Indicate that the booking has extra options.
     */
    public function withExtraOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_extra_options' => true,
        ]);
    }

    /**
     * Create a recent booking.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => $this->faker->dateTimeBetween('-7 days', '+7 days'),
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
