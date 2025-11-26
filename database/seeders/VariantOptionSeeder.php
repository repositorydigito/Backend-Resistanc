<?php

namespace Database\Seeders;

use App\Models\VariantOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VariantOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Variantes de productos');
        // Variantes de productos

        VariantOption::insert([
            [
                'name' => 'S',
                'product_option_type_id' => 1, // Talla
                'value' => 'S',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'M',
                'product_option_type_id' => 1, // Talla
                'value' => 'M',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'L',
                'product_option_type_id' => 1, // Talla
                'value' => 'L',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rojo',
                'product_option_type_id' => 2, // Color
                'value' => '#FF0000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Azul',
                'product_option_type_id' => 2, // Color
                'value' => '#0000FF',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Verde',
                'product_option_type_id' => 2, // Color
                'value' => '#00FF00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Negro',
                'product_option_type_id' => 2, // Color
                'value' => '#000000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Fin variantes de productos
    }
}
