<?php

namespace Database\Seeders;

use App\Models\ProductOptionType;
use App\Models\VariantOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductOptionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear tipos de opciones usando updateOrCreate para evitar duplicados
        $talla = ProductOptionType::updateOrCreate(
            ['slug' => 'talla'],
            [
            'name' => 'Talla',
            'is_color' => false,
            'is_required' => true,
            'is_active' => true,
            ]
        );

        $color = ProductOptionType::updateOrCreate(
            ['slug' => 'color'],
            [
            'name' => 'Color',
            'is_color' => true,
            'is_required' => false,
            'is_active' => true,
            ]
        );

        $sabor = ProductOptionType::updateOrCreate(
            ['slug' => 'sabor'],
            [
            'name' => 'Sabor',
            'is_color' => false,
            'is_required' => false,
            'is_active' => true,
            ]
        );

        // Crear opciones de variante para talla
        $tallas = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($tallas as $tallaValue) {
            VariantOption::updateOrCreate(
                [
                'name' => $tallaValue,
                'product_option_type_id' => $talla->id,
                ],
                [
                'value' => $tallaValue,
                ]
            );
        }

        // Crear opciones de variante para color
        $colores = ['Negro', 'Blanco', 'Gris', 'Azul', 'Rojo', 'Verde'];
        foreach ($colores as $colorValue) {
            VariantOption::updateOrCreate(
                [
                'name' => $colorValue,
                'product_option_type_id' => $color->id,
                ],
                [
                'value' => $colorValue,
                ]
            );
        }

        // Crear opciones de variante para sabor
        $sabores = ['Vainilla', 'Chocolate', 'Fresa', 'Plátano', 'Coco'];
        foreach ($sabores as $saborValue) {
            VariantOption::updateOrCreate(
                [
                'name' => $saborValue,
                'product_option_type_id' => $sabor->id,
                ],
                [
                'value' => $saborValue,
                ]
            );
        }
    }
}
