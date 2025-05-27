<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPackage>
 */
class UserPackageFactory extends Factory
{
    protected $model = UserPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $validityDays = $this->faker->numberBetween(30, 365);

        $startDate = Carbon::parse($purchaseDate)->addDays($this->faker->numberBetween(0, 7));
        $expiryDate = $startDate->copy()->addDays($validityDays);

        // Determine if package is active based on dates
        $isActive = $startDate->isPast() && $expiryDate->isFuture();
        $status = $this->determineStatus($startDate, $expiryDate, $isActive);

        // Calculate credits
        $originalCredits = $this->faker->numberBetween(5, 50);
        $usedCredits = $this->calculateUsedCredits($originalCredits, $status, $startDate);
        $remainingCredits = max(0, $originalCredits - $usedCredits);

        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'package_code' => $this->generatePackageCode(),
            'total_classes' => $originalCredits,
            'used_classes' => $usedCredits,
            'remaining_classes' => $remainingCredits,
            'amount_paid_soles' => $this->faker->randomFloat(2, 299.00, 3999.00),
            'currency' => 'PEN',
            'purchase_date' => $purchaseDate->format('Y-m-d'),
            'activation_date' => $isActive ? $startDate->format('Y-m-d') : null,
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'status' => $status,
            'auto_renew' => $this->faker->boolean(30), // 30% have auto-renewal
            'renewal_price' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 299.00, 999.00) : null,
            'benefits_included' => json_encode($this->generateBenefits()),
            'notes' => $this->generateNotes(),
            'created_at' => $purchaseDate,
            'updated_at' => $this->faker->dateTimeBetween($purchaseDate, 'now'),
        ];
    }

    /**
     * Determine package status based on dates and activity.
     */
    private function determineStatus(Carbon $startDate, Carbon $expiryDate, bool $isActive): string
    {
        if ($startDate->isFuture()) {
            return 'pending';
        }

        if ($expiryDate->isPast()) {
            return 'expired';
        }

        if ($isActive) {
            return $this->faker->randomElement(['active', 'active', 'active', 'suspended']);
        }

        return 'inactive';
    }

    /**
     * Calculate used credits based on package status and time elapsed.
     */
    private function calculateUsedCredits(int $originalCredits, string $status, Carbon $startDate): int
    {
        if ($status === 'pending') {
            return 0;
        }

        if ($status === 'expired') {
            // Expired packages usually have most credits used
            return $this->faker->numberBetween((int)($originalCredits * 0.7), $originalCredits);
        }

        if ($status === 'active') {
            // Active packages have varying usage
            $daysElapsed = $startDate->diffInDays(now());
            $usageRate = min(1.0, $daysElapsed / 60); // Assume 60 days average usage period
            $maxUsed = (int)($originalCredits * $usageRate);
            return $this->faker->numberBetween(0, $maxUsed);
        }

        return $this->faker->numberBetween(0, (int)($originalCredits * 0.5));
    }

    /**
     * Generate package code.
     */
    private function generatePackageCode(): string
    {
        return 'PKG-' . strtoupper($this->faker->bothify('??##??##??'));
    }

    /**
     * Generate benefits included.
     */
    private function generateBenefits(): array
    {
        return $this->faker->randomElements([
            'Acceso a vestuarios premium',
            'Toallas incluidas',
            'Agua purificada gratis',
            'App móvil para reservas',
            'Flexibilidad de horarios',
            'Reserva con anticipación',
            'Descuentos en productos',
            'Clases grupales ilimitadas',
            'Asesoría nutricional',
            'Evaluación física inicial',
        ], $this->faker->numberBetween(3, 6));
    }

    /**
     * Generate package notes.
     */
    private function generateNotes(): ?string
    {
        $notes = [
            'Compra en promoción Black Friday',
            'Cliente frecuente - descuento aplicado',
            'Primera compra - bienvenida',
            'Renovación automática activada',
            'Paquete regalo de cumpleaños',
            'Compra grupal con descuento',
            'Migración desde paquete anterior',
            'Compensación por inconvenientes',
            'Paquete corporativo',
            'Estudiante - descuento especial',
        ];

        return $this->faker->boolean(25) ? $this->faker->randomElement($notes) : null;
    }

    /**
     * Indicate that the package is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->subDays($this->faker->numberBetween(1, 30));
            $expiryDate = $startDate->copy()->addDays($this->faker->numberBetween(30, 90));

            return [
                'activation_date' => $startDate,
                'expiry_date' => $expiryDate,
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the package is expired.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->subDays($this->faker->numberBetween(60, 180));
            $expiryDate = now()->subDays($this->faker->numberBetween(1, 30));
            $originalCredits = $attributes['original_credits'] ?? 10;

            return [
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'is_active' => false,
                'status' => 'expired',
                'used_credits' => $this->faker->numberBetween((int)($originalCredits * 0.7), $originalCredits),
                'remaining_credits' => 0,
            ];
        });
    }

    /**
     * Indicate that the package is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = now()->addDays($this->faker->numberBetween(1, 7));
            $expiryDate = $startDate->copy()->addDays($this->faker->numberBetween(30, 90));

            return [
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'is_active' => false,
                'status' => 'pending',
                'used_credits' => 0,
                'remaining_credits' => $attributes['original_credits'] ?? 10,
            ];
        });
    }

    /**
     * Indicate that the package is a gift.
     */
    public function gift(): static
    {
        return $this->state(fn (array $attributes) => [
            'gift_from_user_id' => User::factory(),
            'gift_message' => $this->faker->randomElement([
                '¡Feliz cumpleaños! Disfruta tus clases',
                'Para que empieces tu journey fitness',
                'Un regalo para tu bienestar',
                '¡Felicitaciones por tu logro!',
                'Para compartir momentos de wellness',
            ]),
            'notes' => 'Paquete regalo',
        ]);
    }

    /**
     * Indicate that the package has auto-renewal.
     */
    public function autoRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_renew' => true,
            'renewal_price' => $this->faker->randomFloat(2, 299.00, 999.00),
            'notes' => 'Renovación automática activada',
        ]);
    }

    /**
     * Create a starter package.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_classes' => 5,
            'amount_paid_soles' => 299.00,
        ]);
    }

    /**
     * Create a premium package.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_classes' => 20,
            'amount_paid_soles' => 999.00,
        ]);
    }

    /**
     * Create an unlimited package.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_classes' => 999,
            'used_classes' => 0,
            'remaining_classes' => 999,
            'amount_paid_soles' => 1999.00,
            'notes' => 'Paquete ilimitado',
        ]);
    }

    /**
     * Create a recently purchased package.
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            $purchaseDate = $this->faker->dateTimeBetween('-7 days', 'now');
            $startDate = Carbon::parse($purchaseDate);
            $expiryDate = $startDate->copy()->addDays(30);

            return [
                'purchase_date' => $purchaseDate,
                'start_date' => $startDate,
                'expiry_date' => $expiryDate,
                'is_active' => true,
                'status' => 'active',
                'used_credits' => $this->faker->numberBetween(0, 3),
            ];
        });
    }
}
