<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $packages = [
            [
                'name' => 'Paquete Starter',
                'type' => 'class_credits',
                'credits' => 5,
                'price' => 299.00,
                'validity_days' => 30,
                'description' => 'Perfecto para comenzar tu journey fitness',
            ],
            [
                'name' => 'Paquete Básico',
                'type' => 'class_credits',
                'credits' => 10,
                'price' => 549.00,
                'validity_days' => 45,
                'description' => 'Ideal para mantener una rutina regular',
            ],
            [
                'name' => 'Paquete Premium',
                'type' => 'class_credits',
                'credits' => 20,
                'price' => 999.00,
                'validity_days' => 60,
                'description' => 'Para los más comprometidos con su bienestar',
            ],
            [
                'name' => 'Membresía Mensual Unlimited',
                'type' => 'unlimited',
                'credits' => null,
                'price' => 399.00,
                'validity_days' => 30,
                'description' => 'Clases ilimitadas por 30 días',
            ],
            [
                'name' => 'Membresía Trimestral',
                'type' => 'unlimited',
                'credits' => null,
                'price' => 1099.00,
                'validity_days' => 90,
                'description' => 'Clases ilimitadas por 3 meses con descuento',
            ],
            [
                'name' => 'Membresía Anual VIP',
                'type' => 'unlimited',
                'credits' => null,
                'price' => 3999.00,
                'validity_days' => 365,
                'description' => 'Acceso completo por un año con beneficios exclusivos',
            ],
            [
                'name' => 'Paquete Cycling Intensivo',
                'type' => 'discipline_specific',
                'credits' => 15,
                'price' => 799.00,
                'validity_days' => 45,
                'description' => 'Especializado en clases de cycling',
            ],
            [
                'name' => 'Paquete Pilates & Wellness',
                'type' => 'discipline_specific',
                'credits' => 12,
                'price' => 699.00,
                'validity_days' => 60,
                'description' => 'Enfocado en pilates y bienestar',
            ],
        ];

        $package = $this->faker->randomElement($packages);

        $features = $this->generateFeatures($package['type'], $package['credits']);
        $restrictions = $this->generateRestrictions($package['type']);

        return [
            'name' => $package['name'],
            'slug' => $this->generateSlug($package['name']),
            'description' => $package['description'],
            'short_description' => $this->generateShortDescription($package['name']),
            'classes_quantity' => $package['credits'] ?? $this->faker->numberBetween(1, 50),
            'price_soles' => $package['price'],
            'original_price_soles' => $package['price'] * $this->faker->randomFloat(2, 1.0, 1.3),
            'validity_days' => $package['validity_days'],
            'package_type' => $this->faker->randomElement(['presencial', 'virtual', 'mixto']),
            'billing_type' => $this->getBillingType($package['type']),
            'is_virtual_access' => $this->faker->boolean(30),
            'priority_booking_days' => $this->faker->numberBetween(0, 7),
            'auto_renewal' => $this->faker->boolean(20),
            'is_featured' => $this->faker->boolean(25),
            'is_popular' => $this->faker->boolean(15),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
            'display_order' => $this->faker->numberBetween(1, 100),
            'features' => json_encode($features),
            'restrictions' => json_encode($restrictions),
            'target_audience' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', 'all']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate features based on package type.
     */
    private function generateFeatures(string $type, ?int $credits): array
    {
        $baseFeatures = [
            'Acceso a vestuarios premium',
            'Toallas incluidas',
            'Agua purificada gratis',
            'App móvil para reservas',
        ];

        $typeFeatures = [
            'class_credits' => [
                "Válido para {$credits} clases",
                'Flexibilidad de horarios',
                'Reserva hasta con 30 días de anticipación',
            ],
            'unlimited' => [
                'Clases ilimitadas',
                'Acceso prioritario a clases populares',
                'Invitaciones a eventos especiales',
                'Descuentos en productos',
            ],
            'discipline_specific' => [
                'Especializado en disciplina específica',
                'Instructores certificados',
                'Equipamiento premium incluido',
            ],
        ];

        return array_merge($baseFeatures, $typeFeatures[$type] ?? []);
    }

    /**
     * Generate restrictions based on package type.
     */
    private function generateRestrictions(string $type): array
    {
        $baseRestrictions = [
            'No válido en días feriados especiales',
            'Sujeto a disponibilidad',
            'Una reserva por clase',
        ];

        $typeRestrictions = [
            'class_credits' => [
                'Los créditos no se acumulan',
                'Válido solo durante el período especificado',
            ],
            'unlimited' => [
                'Máximo 2 clases por día',
                'No incluye clases especiales premium',
            ],
            'discipline_specific' => [
                'Válido solo para la disciplina especificada',
                'No intercambiable por otras disciplinas',
            ],
        ];

        return array_merge($baseRestrictions, $typeRestrictions[$type] ?? []);
    }

    /**
     * Get max bookings per day based on type.
     */
    private function getMaxBookingsPerDay(string $type): int
    {
        return match ($type) {
            'unlimited' => 2,
            'class_credits' => 1,
            'discipline_specific' => 1,
            default => 1,
        };
    }

    /**
     * Generate a unique slug from the package name.
     */
    private function generateSlug(string $name): string
    {
        $baseSlug = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['-', 'a', 'e', 'i', 'o', 'u', 'n'], $name));
        $timestamp = now()->format('YmdHisu'); // Incluye microsegundos
        $random = $this->faker->numberBetween(100000, 999999); // Número más grande
        return $baseSlug . '-' . $timestamp . '-' . $random;
    }

    /**
     * Generate short description.
     */
    private function generateShortDescription(string $name): string
    {
        return "Paquete {$name} - Ideal para tu rutina de entrenamiento";
    }

    /**
     * Get billing type based on package type.
     */
    private function getBillingType(string $type): string
    {
        return match ($type) {
            'unlimited' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'class_credits' => 'one_time',
            'discipline_specific' => 'one_time',
            default => 'one_time',
        };
    }

    /**
     * Indicate that the package is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the package is unlimited.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_type' => 'monthly',
            'classes_quantity' => 999, // Unlimited represented as high number
            'package_type' => 'mixto',
        ]);
    }

    /**
     * Create a starter package.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Paquete Starter',
            'slug' => $this->generateSlug('Paquete Starter'),
            'package_type' => 'presencial',
            'billing_type' => 'one_time',
            'price_soles' => 299.00,
            'classes_quantity' => 5,
            'validity_days' => 30,
        ]);
    }

    /**
     * Create a premium package.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Paquete Premium',
            'slug' => $this->generateSlug('Paquete Premium'),
            'package_type' => 'presencial',
            'billing_type' => 'one_time',
            'price_soles' => 999.00,
            'classes_quantity' => 20,
            'validity_days' => 60,
        ]);
    }

    /**
     * Create a monthly unlimited package.
     */
    public function monthlyUnlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Membresía Mensual Unlimited',
            'slug' => $this->generateSlug('Membresía Mensual Unlimited'),
            'package_type' => 'mixto',
            'billing_type' => 'monthly',
            'price_soles' => 399.00,
            'classes_quantity' => 999,
            'validity_days' => 30,
        ]);
    }
}
