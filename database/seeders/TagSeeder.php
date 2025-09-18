<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tag::insert([
            ['name' => 'Fitness', 'description' => 'Contenido relacionado con el fitness y entrenamiento físico.'],
            ['name' => 'Nutrición', 'description' => 'Consejos y recetas de nutrición saludable.'],
            ['name' => 'Bienestar', 'description' => 'Artículos sobre bienestar mental y emocional.'],
            ['name' => 'Motivación', 'description' => 'Frases y consejos motivacionales para mantenerte activo.'],
            ['name' => 'Eventos', 'description' => 'Información sobre eventos y actividades en RSISTANC.'],
        ]);
    }
}
