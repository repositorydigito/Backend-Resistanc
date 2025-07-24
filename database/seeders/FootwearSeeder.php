<?php

namespace Database\Seeders;

use App\Models\Footwear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FootwearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeders de calzado');

        $footwears = Footwear::insert([
            [
                'code' => '1001',
                'model' => 'Air Max',
                'brand' => 'Nike',
                'size' => 42,
                'color' => 'Blanco/Rojo',
                'type' => 'sneakers',
                'gender' => 'unisex',
                'description' => 'Zapatillas deportivas para entrenamiento',
                'status' => 'available'
            ],
            [
                'code' => '1002',
                'model' => 'Classic',
                'brand' => 'Timberland',
                'size' => 40,
                'color' => 'Amarillo',
                'type' => 'boots',
                'gender' => 'male',
                'description' => 'Botas resistentes para crossfit',
                'status' => 'available'
            ],
            [
                'code' => '1003',
                'model' => 'Flip Flop',
                'brand' => 'Havaianas',
                'size' => 39,
                'color' => 'Azul',
                'type' => 'sandals',
                'gender' => 'female',
                'description' => 'Sandalias ligeras para zona de piscina',
                'status' => 'available'
            ],
            [
                'code' => '1004',
                'model' => 'Oxford',
                'brand' => 'Clarks',
                'size' => 41,
                'color' => 'Negro',
                'type' => 'formal',
                'gender' => 'male',
                'description' => 'Zapatos formales para áreas administrativas',
                'status' => 'available'
            ],
            [
                'code' => '1005',
                'model' => 'Ultraboost',
                'brand' => 'Adidas',
                'size' => 38,
                'color' => 'Negro',
                'type' => 'sneakers',
                'gender' => 'female',
                'description' => 'Zapatillas running con máxima amortiguación',
                'status' => 'available'
            ],
            [
                'code' => '1006',
                'model' => 'Free Run',
                'brand' => 'Nike',
                'size' => 37,
                'color' => 'Rosado',
                'type' => 'sneakers',
                'gender' => 'female',
                'description' => 'Zapatillas para entrenamiento ligero',
                'status' => 'available'
            ],
            [
                'code' => '1007',
                'model' => 'Chuck Taylor',
                'brand' => 'Converse',
                'size' => 43,
                'color' => 'Blanco',
                'type' => 'sneakers',
                'gender' => 'unisex',
                'description' => 'Zapatillas clásicas de lona',
                'status' => 'available'
            ],
            [
                'code' => '1008',
                'model' => 'Work Boot',
                'brand' => 'Caterpillar',
                'size' => 44,
                'color' => 'Marrón',
                'type' => 'boots',
                'gender' => 'male',
                'description' => 'Botas de seguridad para áreas técnicas',
                'status' => 'available'
            ]
        ]);
    }
}
