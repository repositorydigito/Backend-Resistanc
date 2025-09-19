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

        $this->command->info('🌱 Seeders de Disciplinas');
        // Disciplinas

        $disciplines = Discipline::insert([
            [
                'id' => 1,
                'name' => 'Cycling',
                'order' => 1,
                'display_name' => 'Cycling',
                'description' => 'Entrenamiento cardiovascular intenso en bicicletas estáticas con música energizante y luces dinámicas. Quema calorías mientras te diviertes en un ambiente motivador.',

                'color_hex' => '#945527',
                'equipment_required' => json_encode(['bicicleta_estática', 'toalla', 'botella_agua', 'zapatillas_deportivas']),
                'difficulty_level' => 'all_levels',
                'calories_per_hour_avg' => 600,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Reformer',
                'order' => 2,
                'display_name' => 'Reformer',
                'description' => 'Entrenamiento de fuerza y flexibilidad en una reforma de Pilates. Fortalece todo el cuerpo y mejora la postura.',

                'color_hex' => '#9D5AA9',
                'equipment_required' => json_encode(['reformer', 'mat', 'props', 'calcetines_antideslizantes']),
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 350,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Pilates',
                'order' => 3,
                'display_name' => 'Pilates',
                'description' => 'Clases de Pilates en colchoneta para tonificar y fortalecer el core. Ideal para principiantes.',

                'color_hex' => '#2F59A6',
                'equipment_required' => json_encode(['mat', 'pelota', 'banda_elastica', 'bloque']),
                'difficulty_level' => 'beginner',
                'calories_per_hour_avg' => 250,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => 4,
                'name' => 'Box',
                'order' => 4,
                'display_name' => 'Box',
                'description' => 'Clases de Box para mejorar la resistencia y la fuerza. Ideal para todos los niveles.',

                'color_hex' => '#FF5733',
                'equipment_required' => json_encode(['guantes', 'saco_de_boxeo', 'cuerda', 'botella_agua']),
                'difficulty_level' => 'intermediate',
                'calories_per_hour_avg' => 250,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);

        // Fin discipinas
    }
}
