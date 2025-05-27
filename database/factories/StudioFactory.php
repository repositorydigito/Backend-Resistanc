<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Studio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Studio>
 */
class StudioFactory extends Factory
{
    protected $model = Studio::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $studioTypes = ['cycling', 'reformer', 'mat', 'multipurpose'];
        $studioType = $this->faker->randomElement($studioTypes);

        $equipmentByType = [
            'cycling' => ['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores', 'toallas'],
            'reformer' => ['reformers', 'props', 'pelotas', 'bandas_elásticas', 'bloques'],
            'mat' => ['colchonetas', 'pelotas', 'bandas_elásticas', 'bloques', 'correas'],
            'multipurpose' => ['colchonetas', 'pelotas', 'bandas_elásticas', 'barras', 'pesas_ligeras'],
        ];

        $amenitiesByType = [
            'cycling' => ['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis'],
            'reformer' => ['vestuarios', 'duchas', 'casilleros', 'agua_fría'],
            'mat' => ['vestuarios', 'casilleros', 'agua_fría'],
            'multipurpose' => ['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'espejo_pared'],
        ];

        $capacityByType = [
            'cycling' => $this->faker->numberBetween(20, 35),
            'reformer' => $this->faker->numberBetween(8, 15),
            'mat' => $this->faker->numberBetween(15, 25),
            'multipurpose' => $this->faker->numberBetween(12, 20),
        ];

        $studioNames = [
            'cycling' => ['Cycling Studio A', 'Cycling Studio B', 'Energy Room', 'Power Cycling'],
            'reformer' => ['Reformer Studio 1', 'Reformer Studio 2', 'Pilates Room', 'Solid Studio'],
            'mat' => ['Mat Studio', 'Zen Room', 'Mindful Space', 'Flow Studio'],
            'multipurpose' => ['Multi Studio', 'Flex Room', 'Wellness Studio', 'Hybrid Space'],
        ];

        return [
            'name' => $this->faker->randomElement($studioNames[$studioType]),
            'location' => $this->faker->randomElement([
                'Planta Baja', 'Primer Piso', 'Segundo Piso', 'Tercer Piso'
            ]),
            'max_capacity' => $capacityByType[$studioType],
            'equipment_available' => json_encode($equipmentByType[$studioType]),
            'amenities' => json_encode($amenitiesByType[$studioType]),
            'studio_type' => $studioType,
            'is_active' => $this->faker->boolean(95), // 95% activos
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the studio is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the studio is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a cycling studio.
     */
    public function cycling(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Cycling Studio A', 'Cycling Studio B', 'Energy Room']),
            'studio_type' => 'cycling',
            'max_capacity' => $this->faker->numberBetween(20, 35),
            'equipment_available' => json_encode(['bicicletas_estáticas', 'sistema_audio', 'luces_led', 'ventiladores']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'toallas_gratis']),
        ]);
    }

    /**
     * Create a reformer studio.
     */
    public function reformer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Reformer Studio 1', 'Reformer Studio 2', 'Solid Studio']),
            'studio_type' => 'reformer',
            'max_capacity' => $this->faker->numberBetween(8, 15),
            'equipment_available' => json_encode(['reformers', 'props', 'pelotas', 'bandas_elásticas']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría']),
        ]);
    }

    /**
     * Create a mat studio.
     */
    public function mat(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Mat Studio', 'Zen Room', 'Mindful Space']),
            'studio_type' => 'mat',
            'max_capacity' => $this->faker->numberBetween(15, 25),
            'equipment_available' => json_encode(['colchonetas', 'pelotas', 'bandas_elásticas', 'bloques']),
            'amenities' => json_encode(['vestuarios', 'casilleros', 'agua_fría']),
        ]);
    }

    /**
     * Create a multipurpose studio.
     */
    public function multipurpose(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Multi Studio', 'Flex Room', 'Wellness Studio']),
            'studio_type' => 'multipurpose',
            'max_capacity' => $this->faker->numberBetween(12, 20),
            'equipment_available' => json_encode(['colchonetas', 'pelotas', 'bandas_elásticas', 'barras', 'pesas_ligeras']),
            'amenities' => json_encode(['vestuarios', 'duchas', 'casilleros', 'agua_fría', 'espejo_pared']),
        ]);
    }
}
