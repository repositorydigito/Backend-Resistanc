<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductTag>
 */
class ProductTagFactory extends Factory
{
    protected $model = ProductTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a unique tag name using timestamp to guarantee uniqueness
        $baseNames = [
            'Tag', 'Etiqueta', 'Label', 'Marca', 'Tipo',
            'Categoría', 'Estilo', 'Variante', 'Opción', 'Característica',
        ];

        $baseName = $this->faker->randomElement($baseNames);
        $timestamp = now()->format('YmdHis') . $this->faker->numberBetween(100, 999);
        $tagName = $baseName . ' ' . $timestamp;

        return [
            'name' => $tagName,
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Generate a description for the tag.
     */
    private function generateDescription(string $tagName): string
    {
        $descriptions = [
            'Nuevo' => 'Productos recién llegados a nuestra tienda',
            'Bestseller' => 'Los productos más vendidos y populares',
            'Oferta' => 'Productos con descuentos especiales',
            'Limitado' => 'Edición limitada, disponibilidad restringida',
            'Exclusivo' => 'Productos exclusivos de RSISTANC',
            'Algodón Orgánico' => 'Fabricado con algodón 100% orgánico',
            'Transpirable' => 'Material que permite la ventilación',
            'Antibacterial' => 'Tratamiento antibacterial incorporado',
            'Secado Rápido' => 'Tecnología de secado rápido',
            'Eco-Friendly' => 'Productos amigables con el medio ambiente',
            'Cycling' => 'Ideal para clases de cycling',
            'Pilates' => 'Perfecto para práctica de pilates',
            'Yoga' => 'Diseñado para yoga y meditación',
            'Reformer' => 'Específico para pilates reformer',
            'Barre' => 'Ideal para clases de barre',
            'Mujer' => 'Diseñado específicamente para mujeres',
            'Hombre' => 'Diseñado específicamente para hombres',
            'Unisex' => 'Apto para todos los géneros',
            'Talla Plus' => 'Disponible en tallas grandes',
        ];

        return $descriptions[$tagName] ?? 'Etiqueta de producto';
    }

    /**
     * Create a new product tag.
     */
    public function nuevo(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Nuevo',
        ]);
    }

    /**
     * Create a bestseller tag.
     */
    public function bestseller(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bestseller',
        ]);
    }

    /**
     * Create an offer tag.
     */
    public function oferta(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Oferta',
        ]);
    }
}
