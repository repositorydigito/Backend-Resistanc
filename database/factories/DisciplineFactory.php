<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Discipline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discipline>
 */
class DisciplineFactory extends Factory
{
    protected $model = Discipline::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $disciplines = [
            [
                'name' => 'cycling',
                'display_name' => 'Cycling',
                'description' => 'Entrenamiento cardiovascular intenso en bicicletas estáticas con música energizante.',
                'color_hex' => '#FF6B35',
                'equipment_required' => ['bicicleta_estática', 'toalla', 'botella_agua'],
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 600,
            ],
            [
                'name' => 'solidreformer',
                'display_name' => 'Solid Reformer',
                'description' => 'Pilates en máquina reformer para fortalecer y tonificar todo el cuerpo.',
                'color_hex' => '#4ECDC4',
                'equipment_required' => ['reformer', 'mat', 'props'],
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 350,
            ],
            [
                'name' => 'pilates_mat',
                'display_name' => 'Pilates Mat',
                'description' => 'Pilates en colchoneta enfocado en core, flexibilidad y control corporal.',
                'color_hex' => '#95E1D3',
                'equipment_required' => ['mat', 'pelota', 'banda_elastica'],
                'difficulty_level' => 'beginner',
                'calories_per_hour_avg' => 250,
            ],
            [
                'name' => 'yoga',
                'display_name' => 'Yoga',
                'description' => 'Práctica de yoga para mejorar flexibilidad, fuerza y bienestar mental.',
                'color_hex' => '#A8E6CF',
                'equipment_required' => ['mat', 'bloque', 'correa'],
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 200,
            ],
            [
                'name' => 'barre',
                'display_name' => 'Barre',
                'description' => 'Entrenamiento inspirado en ballet que combina pilates, yoga y danza.',
                'color_hex' => '#FFB6C1',
                'equipment_required' => ['barra', 'mat', 'pelotas_pequeñas'],
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 300,
            ],
        ];

        $discipline = $this->faker->randomElement($disciplines);

        return [
            'name' => $discipline['name'],
            'display_name' => $discipline['display_name'],
            'description' => $discipline['description'],
            'icon_url' => '/images/disciplines/' . $discipline['name'] . '.svg',
            'color_hex' => $discipline['color_hex'],
            'equipment_required' => json_encode($discipline['equipment_required']),
            'difficulty_level' => $discipline['difficulty_level'],
            'calories_per_hour_avg' => $discipline['calories_per_hour_avg'],
            'is_active' => $this->faker->boolean(95), // 95% activas
            'sort_order' => $this->faker->numberBetween(1, 100),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the discipline is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the discipline is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a cycling discipline.
     */
    public function cycling(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'cycling',
            'display_name' => 'Cycling',
            'description' => 'Entrenamiento cardiovascular intenso en bicicletas estáticas con música energizante.',
            'color_hex' => '#FF6B35',
            'equipment_required' => json_encode(['bicicleta_estática', 'toalla', 'botella_agua']),
            'difficulty_level' => 'all_levels',
            'calories_per_hour_avg' => 600,
        ]);
    }

    /**
     * Create a pilates reformer discipline.
     */
    public function reformer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'solidreformer',
            'display_name' => 'Solid Reformer',
            'description' => 'Pilates en máquina reformer para fortalecer y tonificar todo el cuerpo.',
            'color_hex' => '#4ECDC4',
            'equipment_required' => json_encode(['reformer', 'mat', 'props']),
            'difficulty_level' => 'intermediate',
            'calories_per_hour_avg' => 350,
        ]);
    }
}
