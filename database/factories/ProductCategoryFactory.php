<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            [
                'name' => 'Ropa Deportiva',
                'description' => 'Ropa cómoda y funcional para entrenar',
                'slug' => 'ropa-deportiva',
            ],
            [
                'name' => 'Accesorios',
                'description' => 'Accesorios para complementar tu entrenamiento',
                'slug' => 'accesorios',
            ],
            [
                'name' => 'Equipamiento',
                'description' => 'Equipos y herramientas para entrenar en casa',
                'slug' => 'equipamiento',
            ],
            [
                'name' => 'Nutrición',
                'description' => 'Suplementos y productos nutricionales',
                'slug' => 'nutricion',
            ],
            [
                'name' => 'Bienestar',
                'description' => 'Productos para el cuidado y bienestar personal',
                'slug' => 'bienestar',
            ],
            [
                'name' => 'Membresías',
                'description' => 'Planes y paquetes de membresía',
                'slug' => 'membresias',
            ],
        ];

        $category = $this->faker->randomElement($categories);

        return [
            'name' => $category['name'],
            'description' => $category['description'],
            'slug' => $category['slug'],
            'parent_id' => null, // No parent categories for now
            'image_url' => '/images/categories/' . $category['slug'] . '.jpg',
            'is_active' => $this->faker->boolean(95), // 95% activas
            'sort_order' => $this->faker->numberBetween(1, 100),
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
            'updated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }

    /**
     * Indicate that the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a clothing category.
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Ropa Deportiva',
            'description' => 'Ropa cómoda y funcional para entrenar',
            'slug' => 'ropa-deportiva',
        ]);
    }

    /**
     * Create an accessories category.
     */
    public function accessories(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Accesorios',
            'description' => 'Accesorios para complementar tu entrenamiento',
            'slug' => 'accesorios',
        ]);
    }

    /**
     * Create a membership category.
     */
    public function membership(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Membresías',
            'description' => 'Planes y paquetes de membresía',
            'slug' => 'membresias',
        ]);
    }
}
