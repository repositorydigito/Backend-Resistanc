<?php

namespace Database\Seeders;

use App\Models\ProductTag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Etiquetas de productos');
        // Etiquetas productos
        $tags = [
            [
                'name' => 'Entrenamiento',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Nutrición',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bienestar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        ProductTag::insert($tags);
        // Fin etiquetas productos
    }
}
