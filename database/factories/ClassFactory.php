<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Discipline;
use App\Models\Instructor;
use App\Models\Studio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassModel>
 */
class ClassFactory extends Factory
{
    protected $model = ClassModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $classesByDiscipline = [
            'cycling' => [
                ['name' => 'Power Cycling', 'duration' => 45, 'intensity' => 'high'],
                ['name' => 'Cycling Beats', 'duration' => 50, 'intensity' => 'medium'],
                ['name' => 'Morning Energy Ride', 'duration' => 45, 'intensity' => 'medium'],
                ['name' => 'HIIT Cycling', 'duration' => 30, 'intensity' => 'high'],
                ['name' => 'Endurance Ride', 'duration' => 60, 'intensity' => 'medium'],
                ['name' => 'Beginner Cycling', 'duration' => 40, 'intensity' => 'low'],
            ],
            'solidreformer' => [
                ['name' => 'Reformer Flow', 'duration' => 50, 'intensity' => 'medium'],
                ['name' => 'Advanced Reformer', 'duration' => 55, 'intensity' => 'high'],
                ['name' => 'Reformer Basics', 'duration' => 45, 'intensity' => 'low'],
                ['name' => 'Core Reformer', 'duration' => 45, 'intensity' => 'medium'],
                ['name' => 'Full Body Reformer', 'duration' => 60, 'intensity' => 'medium'],
            ],
            'pilates_mat' => [
                ['name' => 'Mat Pilates Flow', 'duration' => 50, 'intensity' => 'medium'],
                ['name' => 'Core Intensive', 'duration' => 45, 'intensity' => 'high'],
                ['name' => 'Gentle Pilates', 'duration' => 45, 'intensity' => 'low'],
                ['name' => 'Pilates & Stretch', 'duration' => 60, 'intensity' => 'low'],
            ],
            'yoga' => [
                ['name' => 'Vinyasa Flow', 'duration' => 60, 'intensity' => 'medium'],
                ['name' => 'Yin Yoga', 'duration' => 75, 'intensity' => 'low'],
                ['name' => 'Power Yoga', 'duration' => 60, 'intensity' => 'high'],
                ['name' => 'Morning Yoga', 'duration' => 45, 'intensity' => 'low'],
            ],
            'barre' => [
                ['name' => 'Barre Fusion', 'duration' => 50, 'intensity' => 'medium'],
                ['name' => 'Ballet Barre', 'duration' => 55, 'intensity' => 'medium'],
                ['name' => 'Cardio Barre', 'duration' => 45, 'intensity' => 'high'],
            ],
        ];

        // Seleccionar disciplina aleatoria
        $disciplineName = $this->faker->randomElement(array_keys($classesByDiscipline));
        $classes = $classesByDiscipline[$disciplineName];
        $classData = $this->faker->randomElement($classes);

        $name = $classData['name'];
        $duration = $classData['duration'];
        $intensity = $classData['intensity'];

        $difficultyLevel = $this->mapIntensityToDifficulty($intensity);
        $musicGenres = ['Pop', 'Electronic', 'Hip Hop', 'Rock', 'Latin', 'Reggaeton', 'House', 'Chill'];

        return [
            'discipline_id' => Discipline::factory(),
            'instructor_id' => Instructor::factory(),
            'studio_id' => Studio::factory(),
            'name' => $name,
            'description' => $this->generateDescription($name, $disciplineName, $intensity),
            'duration_minutes' => $duration,
            'max_participants' => $this->getMaxParticipants($disciplineName),
            'type' => $this->faker->randomElement(['regular', 'workshop', 'private', 'special']),
            'intensity_level' => $intensity,
            'difficulty_level' => $difficultyLevel,
            'music_genre' => $this->faker->randomElement($musicGenres),
            'special_requirements' => $this->generateSpecialRequirements($disciplineName),
            'is_featured' => $this->faker->boolean(25), // 25% son destacadas
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive', 'draft']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Map intensity to difficulty level.
     */
    private function mapIntensityToDifficulty(string $intensity): string
    {
        return match ($intensity) {
            'low' => $this->faker->randomElement(['beginner', 'all_levels']),
            'medium' => $this->faker->randomElement(['intermediate', 'all_levels']),
            'high' => $this->faker->randomElement(['advanced', 'intermediate']),
            default => 'all_levels',
        };
    }

    /**
     * Get max participants based on discipline.
     */
    private function getMaxParticipants(string $discipline): int
    {
        return match ($discipline) {
            'cycling' => $this->faker->numberBetween(20, 35),
            'solidreformer' => $this->faker->numberBetween(8, 15),
            'pilates_mat' => $this->faker->numberBetween(15, 25),
            'yoga' => $this->faker->numberBetween(15, 25),
            'barre' => $this->faker->numberBetween(12, 20),
            default => $this->faker->numberBetween(10, 20),
        };
    }

    /**
     * Generate class description.
     */
    private function generateDescription(string $name, string $discipline, string $intensity): string
    {
        $descriptions = [
            'cycling' => [
                'low' => "Una clase de cycling perfecta para principiantes. {$name} te introducirá al mundo del ciclismo indoor con música motivadora y un ambiente acogedor.",
                'medium' => "Clase de cycling {$name} con intensidad moderada. Combina cardio efectivo con diversión, perfecta para mantener tu condición física.",
                'high' => "Clase intensa de cycling {$name}. Prepárate para un entrenamiento cardiovascular desafiante que llevará tu resistencia al límite.",
            ],
            'solidreformer' => [
                'low' => "Introducción al pilates reformer con {$name}. Aprende los fundamentos en un ambiente relajado y controlado.",
                'medium' => "Clase de reformer {$name} que combina fuerza, flexibilidad y control. Ideal para tonificar y fortalecer todo el cuerpo.",
                'high' => "Clase avanzada {$name} en reformer. Secuencias desafiantes para estudiantes experimentados que buscan llevar su práctica al siguiente nivel.",
            ],
            'pilates_mat' => [
                'low' => "Pilates en colchoneta {$name} enfocado en los fundamentos. Perfecto para desarrollar conciencia corporal y fuerza del core.",
                'medium' => "Clase de mat pilates {$name} que trabaja todo el cuerpo. Combina ejercicios clásicos con variaciones modernas.",
                'high' => "Clase intensiva {$name} de mat pilates. Secuencias avanzadas que desafiarán tu fuerza, flexibilidad y control.",
            ],
            'yoga' => [
                'low' => "Clase de yoga {$name} relajante y restaurativa. Perfecta para reducir el estrés y mejorar la flexibilidad.",
                'medium' => "Práctica de yoga {$name} que combina posturas dinámicas con momentos de calma. Equilibra fuerza y relajación.",
                'high' => "Clase vigorosa de yoga {$name}. Secuencias desafiantes que desarrollan fuerza, flexibilidad y resistencia.",
            ],
            'barre' => [
                'low' => "Introducción al barre con {$name}. Movimientos inspirados en ballet adaptados para todos los niveles.",
                'medium' => "Clase de barre {$name} que combina ballet, pilates y yoga. Tonifica y esculpe todo el cuerpo.",
                'high' => "Clase intensa de barre {$name}. Entrenamiento cardiovascular que combina fuerza y gracia del ballet.",
            ],
        ];

        return $descriptions[$discipline][$intensity] ?? "Clase de {$discipline} {$name} diseñada para ofrecerte una experiencia única de entrenamiento.";
    }

    /**
     * Generate special requirements.
     */
    private function generateSpecialRequirements(string $discipline): ?string
    {
        $requirements = [
            'cycling' => [
                'Traer toalla y botella de agua',
                'Usar ropa cómoda y zapatillas deportivas',
                'Llegar 10 minutos antes para ajustar la bicicleta',
            ],
            'solidreformer' => [
                'Usar calcetines antideslizantes',
                'Ropa ajustada recomendada',
                'No comer 2 horas antes de la clase',
            ],
            'pilates_mat' => [
                'Traer mat propio (opcional)',
                'Ropa cómoda que permita movimiento',
                'Cabello recogido',
            ],
            'yoga' => [
                'Traer mat propio (opcional)',
                'Ropa cómoda y elástica',
                'Evitar comidas pesadas 2 horas antes',
            ],
            'barre' => [
                'Usar calcetines antideslizantes',
                'Ropa ajustada recomendada',
                'Cabello recogido',
            ],
        ];

        $disciplineRequirements = $requirements[$discipline] ?? [];
        
        if (empty($disciplineRequirements)) {
            return null;
        }

        return $this->faker->boolean(70) ? $this->faker->randomElement($disciplineRequirements) : null;
    }

    /**
     * Indicate that the class is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the class is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create a cycling class.
     */
    public function cycling(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Power Cycling', 'Cycling Beats', 'Morning Energy Ride']),
            'duration_minutes' => $this->faker->randomElement([30, 45, 50]),
            'intensity_level' => $this->faker->randomElement(['medium', 'high']),
            'max_participants' => $this->faker->numberBetween(20, 35),
        ]);
    }

    /**
     * Create a reformer class.
     */
    public function reformer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement(['Reformer Flow', 'Advanced Reformer', 'Core Reformer']),
            'duration_minutes' => $this->faker->randomElement([45, 50, 55]),
            'intensity_level' => $this->faker->randomElement(['medium', 'high']),
            'max_participants' => $this->faker->numberBetween(8, 15),
        ]);
    }

    /**
     * Create a beginner class.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'beginner',
            'intensity_level' => 'low',
            'special_requirements' => 'Clase diseñada para principiantes. No se requiere experiencia previa.',
        ]);
    }
}
