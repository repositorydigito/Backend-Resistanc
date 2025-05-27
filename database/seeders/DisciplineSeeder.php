<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Discipline;
use Illuminate\Database\Seeder;

class DisciplineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎯 Seeding disciplines...');

        $disciplines = [
            [
                'name' => 'cycling',
                'display_name' => 'Cycling',
                'description' => 'Entrenamiento cardiovascular intenso en bicicletas estáticas con música energizante y luces dinámicas. Quema calorías mientras te diviertes en un ambiente motivador.',
                'icon_url' => '/images/disciplines/cycling.svg',
                'color_hex' => '#FF6B35',
                'equipment_required' => json_encode(['bicicleta_estática', 'toalla', 'botella_agua', 'zapatillas_deportivas']),
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 600,
                'is_active' => true,
            ],
            [
                'name' => 'solidreformer',
                'display_name' => 'Solid Reformer',
                'description' => 'Pilates en máquina reformer para fortalecer y tonificar todo el cuerpo. Mejora la postura, flexibilidad y fuerza con movimientos controlados y precisos.',
                'icon_url' => '/images/disciplines/solidreformer.svg',
                'color_hex' => '#4ECDC4',
                'equipment_required' => json_encode(['reformer', 'mat', 'props', 'calcetines_antideslizantes']),
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 350,
                'is_active' => true,
            ],
            [
                'name' => 'pilates_mat',
                'display_name' => 'Pilates Mat',
                'description' => 'Pilates en colchoneta enfocado en core, flexibilidad y control corporal. Fortalece desde adentro hacia afuera con ejercicios clásicos y modernos.',
                'icon_url' => '/images/disciplines/pilates_mat.svg',
                'color_hex' => '#95E1D3',
                'equipment_required' => json_encode(['mat', 'pelota', 'banda_elastica', 'bloque']),
                'difficulty_level' => 'beginner',
                'calories_per_hour_avg' => 250,
                'is_active' => true,
            ],
            [
                'name' => 'yoga',
                'display_name' => 'Yoga',
                'description' => 'Práctica milenaria que combina posturas físicas, respiración y meditación. Mejora la flexibilidad, fuerza y bienestar mental en un ambiente de paz.',
                'icon_url' => '/images/disciplines/yoga.svg',
                'color_hex' => '#A8E6CF',
                'equipment_required' => json_encode(['mat', 'bloque', 'correa', 'manta']),
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 200,
                'is_active' => true,
            ],
            [
                'name' => 'barre',
                'display_name' => 'Barre',
                'description' => 'Entrenamiento inspirado en ballet que combina pilates, yoga y danza. Tonifica y esculpe el cuerpo con movimientos gráciles pero desafiantes.',
                'icon_url' => '/images/disciplines/barre.svg',
                'color_hex' => '#FFB6C1',
                'equipment_required' => json_encode(['barra', 'mat', 'pelotas_pequeñas', 'bandas_ligeras']),
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 300,
                'is_active' => true,
            ],
            [
                'name' => 'hiit',
                'display_name' => 'HIIT',
                'description' => 'Entrenamiento de alta intensidad por intervalos. Maximiza la quema de calorías y mejora la condición cardiovascular en sesiones cortas pero intensas.',
                'icon_url' => '/images/disciplines/hiit.svg',
                'color_hex' => '#FF4757',
                'equipment_required' => json_encode(['mat', 'pesas', 'bandas', 'step']),
                'difficulty_level' => 'advanced',
                'calories_per_hour_avg' => 700,
                'is_active' => true,
            ],
            [
                'name' => 'stretching',
                'display_name' => 'Stretching & Recovery',
                'description' => 'Sesiones de estiramiento y recuperación para mejorar la flexibilidad y aliviar tensiones. Perfecto para complementar entrenamientos intensos.',
                'icon_url' => '/images/disciplines/stretching.svg',
                'color_hex' => '#C7ECEE',
                'equipment_required' => json_encode(['mat', 'foam_roller', 'pelotas_masaje', 'correas']),
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 150,
                'is_active' => true,
            ],
        ];

        foreach ($disciplines as $disciplineData) {
            Discipline::create($disciplineData);
            $this->command->line("✅ Created discipline: {$disciplineData['display_name']}");
        }

        $this->command->info("🎉 Created " . count($disciplines) . " disciplines successfully!");
    }
}
