<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Productos de categorias');
        // Categoria productos

        $categories = [
            [
                'name' => 'Ropa Deportiva',
                'slug' => 'ropa-deportiva',
                'description' => 'Ropa cómoda y funcional para tus entrenamientos',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Accesorios Fitness',
                'slug' => 'accesorios-fitness',
                'description' => 'Accesorios para mejorar tu rendimiento deportivo',
                'image_url' => 'https://i.imgur.com/1234567.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        ProductCategory::insert($categories);

        // Fin categorias productos
    }
}
