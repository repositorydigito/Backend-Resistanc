<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StudioLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudioLocation>
 */
class StudioLocationFactory extends Factory
{
    protected $model = StudioLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = [
            [
                'name' => 'RSISTANC Miraflores',
                'address_line' => 'Av. Larco 1234, Miraflores',
                'city' => 'Lima',
                'latitude' => -12.1191,
                'longitude' => -77.0292,
                'phone' => '+51 1 234-5678',
            ],
            [
                'name' => 'RSISTANC San Isidro',
                'address_line' => 'Av. Conquistadores 456, San Isidro',
                'city' => 'Lima',
                'latitude' => -12.0964,
                'longitude' => -77.0428,
                'phone' => '+51 1 345-6789',
            ],
            [
                'name' => 'RSISTANC Surco',
                'address_line' => 'Av. Primavera 789, Santiago de Surco',
                'city' => 'Lima',
                'latitude' => -12.1348,
                'longitude' => -76.9836,
                'phone' => '+51 1 456-7890',
            ],
            [
                'name' => 'RSISTANC La Molina',
                'address_line' => 'Av. Javier Prado Este 2345, La Molina',
                'city' => 'Lima',
                'latitude' => -12.0769,
                'longitude' => -76.9447,
                'phone' => '+51 1 567-8901',
            ],
            [
                'name' => 'RSISTANC Barranco',
                'address_line' => 'Av. Grau 567, Barranco',
                'city' => 'Lima',
                'latitude' => -12.1464,
                'longitude' => -77.0206,
                'phone' => '+51 1 678-9012',
            ],
        ];

        $location = $this->faker->randomElement($locations);

        return [
            'name' => $location['name'],
            'address_line' => $location['address_line'],
            'city' => $location['city'],
            'country' => 'PE',
            'latitude' => $location['latitude'] + $this->faker->randomFloat(4, -0.01, 0.01),
            'longitude' => $location['longitude'] + $this->faker->randomFloat(4, -0.01, 0.01),
            'phone' => $location['phone'],
            'is_active' => $this->faker->boolean(90), // 90% activas
            'created_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the location is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a Miraflores location.
     */
    public function miraflores(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'RSISTANC Miraflores',
            'address_line' => 'Av. Larco 1234, Miraflores',
            'city' => 'Lima',
            'latitude' => -12.1191,
            'longitude' => -77.0292,
            'phone' => '+51 1 234-5678',
        ]);
    }

    /**
     * Create a San Isidro location.
     */
    public function sanIsidro(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'RSISTANC San Isidro',
            'address_line' => 'Av. Conquistadores 456, San Isidro',
            'city' => 'Lima',
            'latitude' => -12.0964,
            'longitude' => -77.0428,
            'phone' => '+51 1 345-6789',
        ]);
    }
}
