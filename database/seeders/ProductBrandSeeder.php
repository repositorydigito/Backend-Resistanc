<?php

namespace Database\Seeders;

use App\Models\ProductBrand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de Marcas');
        // Marca de productos

        ProductBrand::insert([
            [
                'name' => 'RSISTANCE'
            ],
            [
                'name' => 'NIKE'
            ],
            [
                'name' => 'Adidas'
            ]
        ]);

        // Fin marca de productos
    }
}
