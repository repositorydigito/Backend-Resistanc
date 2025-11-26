<?php

namespace Database\Seeders;

use App\Models\ProductOptionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductOptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('🌱 Seeders de atributos de los productos');
        // Tipo de atributos de productos
        $productAttributes = [
            [
                'name' => 'Talla',
                'slug' => 'talla',
                'is_color' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Color',
                'slug' => 'color',
                'is_color' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Modelo',
                'slug' => 'modelo',
                'is_color' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        ProductOptionType::insert($productAttributes);
        // Fin tipo de atributos de productos
    }
}
