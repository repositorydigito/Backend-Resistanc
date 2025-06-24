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
        // Crear tipos de opciones
        $talla = ProductOptionType::create([
            'name' => 'Talla',
            'slug' => 'talla',
            'is_color' => false,
            'is_required' => true,
            'is_active' => true,
        ]);

        $color = ProductOptionType::create([
            'name' => 'Color',
            'slug' => 'color',
            'is_color' => true,
            'is_required' => false,
            'is_active' => true,
        ]);

        $sabor = ProductOptionType::create([
            'name' => 'Sabor',
            'slug' => 'sabor',
            'is_color' => false,
            'is_required' => false,
            'is_active' => true,
        ]);

        // Crear opciones de variante para talla
        $tallas = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($tallas as $tallaValue) {
            VariantOption::create([
                'name' => $tallaValue,
                'product_option_type_id' => $talla->id,
                'value' => $tallaValue,
            ]);
        }

        // Crear opciones de variante para color
        $colores = ['Negro', 'Blanco', 'Gris', 'Azul', 'Rojo', 'Verde'];
        foreach ($colores as $colorValue) {
            VariantOption::create([
                'name' => $colorValue,
                'product_option_type_id' => $color->id,
                'value' => $colorValue,
            ]);
        }

        // Crear opciones de variante para sabor
        $sabores = ['Vainilla', 'Chocolate', 'Fresa', 'Plátano', 'Coco'];
        foreach ($sabores as $saborValue) {
            VariantOption::create([
                'name' => $saborValue,
                'product_option_type_id' => $sabor->id,
                'value' => $saborValue,
            ]);
        }
    }
}
