<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::insert([
            ['name' => 'Entrenamiento', 'slug' => 'entrenamiento', 'description' => 'Artículos relacionados con rutinas de entrenamiento y ejercicios.'],
            ['name' => 'Nutrición', 'slug' => 'nutricion', 'description' => 'Consejos y recetas sobre nutrición saludable.'],
            ['name' => 'Bienestar', 'slug' => 'bienestar', 'description' => 'Contenido sobre bienestar mental y emocional.'],
            ['name' => 'Motivación', 'slug' => 'motivacion', 'description' => 'Frases y consejos motivacionales para mantenerte activo.'],
            ['name' => 'Eventos', 'slug' => 'eventos', 'description' => 'Información sobre eventos y actividades en RSISTANC.'],
        ]);
    }
}
