<?php

namespace Database\Seeders;

use App\Models\ClassModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Clases');
        // Clases

        ClassModel::insert([
            // ===== CYCLING CLASSES =====
            [
                'name' => 'Ride and ride',
                'discipline_id' => 1, // cycling
                'type' => 'presencial',
                'duration_minutes' => 50,
                'max_capacity' => 20,
                'description' => 'Clase de cycling con música energizante y ritmos electrónicos',
                'difficulty_level' => 'all_levels',
                'music_genre' => 'electro',
                'color_hex' => '#9D5AA9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reload',
                'discipline_id' => 1, // cycling
                'type' => 'presencial',
                'duration_minutes' => 45,
                'max_capacity' => 20,
                'description' => 'Entrenamiento intenso de cycling para quemar calorías y ganar resistencia',
                'difficulty_level' => 'advanced',
                'music_genre' => 'rock',
                'color_hex' => '#9D5AA9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'name' => 'Rsistanc',
            //     'discipline_id' => 1, // cycling
            //     'type' => 'presencial',
            //     'duration_minutes' => 30,
            //     'max_capacity' => 15,
            //     'description' => 'Sesión corta pero intensa de cycling perfecto para el lunch break',
            //     'difficulty_level' => 'intermediate',
            //     'music_genre' => 'pop',
            //     'color_hex' => '#9D5AA9',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],

            // ===== SOLID REFORMER CLASSES =====
            // [
            //     'name' => 'Reformer Full Body',
            //     'discipline_id' => 2, // solidreformer
            //     'type' => 'presencial',
            //     'duration_minutes' => 55,
            //     'max_capacity' => 8,
            //     'description' => 'Entrenamiento completo en reformer para fortalecer todo el cuerpo',
            //     'difficulty_level' => 'intermediate',
            //     'music_genre' => 'instrumental',
            //     'color_hex' => '#9D5AA9',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'name' => 'Reformer Core Focus',
            //     'discipline_id' => 2, // solidreformer
            //     'type' => 'presencial',
            //     'duration_minutes' => 45,
            //     'max_capacity' => 10,
            //     'description' => 'Clase especializada en fortalecer el core usando el reformer',
            //     'difficulty_level' => 'advanced',
            //     'music_genre' => 'ambient',
            //     'color_hex' => '#9D5AA9',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],

            // ===== PILATES MAT CLASSES =====
            // [
            //     'name' => 'Pilates Fundamentals',
            //     'discipline_id' => 3, // pilates_mat
            //     'type' => 'presencial',
            //     'duration_minutes' => 50,
            //     'max_capacity' => 15,
            //     'description' => 'Clase de pilates en colchoneta ideal para principiantes',
            //     'difficulty_level' => 'beginner',
            //     'music_genre' => 'classical',
            //     'color_hex' => '#9D5AA9',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'name' => 'Pilates Flow',
            //     'discipline_id' => 3, // pilates_mat
            //     'type' => 'presencial',
            //     'duration_minutes' => 45,
            //     'max_capacity' => 12,
            //     'description' => 'Secuencia fluida de pilates que combina fuerza y flexibilidad',
            //     'difficulty_level' => 'intermediate',
            //     'music_genre' => 'new_age',
            //     'color_hex' => '#9D5AA9',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
        ]);

        // Fin clases
    }
}
