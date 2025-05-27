<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productsByCategory = [
            'ropa-deportiva' => [
                ['name' => 'Top Deportivo RSISTANC', 'price' => 89.90],
                ['name' => 'Leggings High Waist', 'price' => 129.90],
                ['name' => 'Sports Bra Premium', 'price' => 79.90],
                ['name' => 'Tank Top Breathable', 'price' => 69.90],
                ['name' => 'Shorts de Cycling', 'price' => 99.90],
                ['name' => 'Conjunto Pilates', 'price' => 159.90],
            ],
            'accesorios' => [
                ['name' => 'Botella de Agua RSISTANC', 'price' => 39.90],
                ['name' => 'Toalla de Microfibra', 'price' => 29.90],
                ['name' => 'Mat de Yoga Premium', 'price' => 149.90],
                ['name' => 'Banda Elástica Set', 'price' => 49.90],
                ['name' => 'Guantes de Cycling', 'price' => 59.90],
                ['name' => 'Calcetines Antideslizantes', 'price' => 24.90],
            ],
            'equipamiento' => [
                ['name' => 'Pelota de Pilates', 'price' => 79.90],
                ['name' => 'Bloque de Yoga', 'price' => 34.90],
                ['name' => 'Correa de Yoga', 'price' => 29.90],
                ['name' => 'Foam Roller', 'price' => 119.90],
                ['name' => 'Pesas Ligeras Set', 'price' => 89.90],
            ],
            'nutricion' => [
                ['name' => 'Proteína Whey RSISTANC', 'price' => 189.90],
                ['name' => 'Pre-Workout Energy', 'price' => 129.90],
                ['name' => 'BCAA Recovery', 'price' => 149.90],
                ['name' => 'Multivitamínico', 'price' => 79.90],
                ['name' => 'Colágeno Hidrolizado', 'price' => 159.90],
            ],
            'bienestar' => [
                ['name' => 'Aceite Esencial Relajante', 'price' => 49.90],
                ['name' => 'Crema Muscular', 'price' => 39.90],
                ['name' => 'Sales de Baño Recovery', 'price' => 29.90],
                ['name' => 'Masajeador Muscular', 'price' => 199.90],
            ],
        ];

        // Seleccionar categoría aleatoria
        $categorySlug = $this->faker->randomElement(array_keys($productsByCategory));
        $products = $productsByCategory[$categorySlug];
        $product = $this->faker->randomElement($products);

        $name = $product['name'];
        $basePrice = $product['price'];

        // Aplicar variación de precio
        $price = $basePrice * $this->faker->randomFloat(2, 0.8, 1.2);

        // Calcular precio con descuento
        $hasDiscount = $this->faker->boolean(30); // 30% tienen descuento
        $discountPrice = $hasDiscount ? $price * $this->faker->randomFloat(2, 0.7, 0.9) : null;

        $slug = $this->generateSlug($name);
        $sku = $this->generateSku($name);

        return [
            'category_id' => ProductCategory::factory(),
            'name' => $name,
            'slug' => $slug,
            'description' => $this->generateDescription($name, $categorySlug),
            'short_description' => $this->generateShortDescription($name),
            'sku' => $sku,
            'price_soles' => $price,
            'compare_price_soles' => $discountPrice,
            'cost_price_soles' => $price * $this->faker->randomFloat(2, 0.4, 0.7),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'min_stock_alert' => $this->faker->numberBetween(5, 20),
            'weight_grams' => $this->generateWeight($categorySlug),
            'dimensions' => json_encode($this->generateDimensions($categorySlug)),
            'images' => json_encode($this->generateImages($slug)),
            'nutritional_info' => $categorySlug === 'nutricion' ? json_encode($this->generateNutritionalInfo()) : null,
            'ingredients' => $categorySlug === 'nutricion' ? json_encode($this->generateIngredients()) : null,
            'allergens' => $categorySlug === 'nutricion' ? json_encode($this->generateAllergens()) : null,
            'product_type' => $this->getProductType($categorySlug),
            'requires_variants' => $this->faker->boolean(60), // 60% requieren variantes
            'is_virtual' => false,
            'is_featured' => $this->faker->boolean(20), // 20% son destacados
            'is_available_for_booking' => $this->faker->boolean(30), // 30% disponibles para reservas
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
            'meta_title' => $name . ' - RSISTANC',
            'meta_description' => $this->generateMetaDescription($name),
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate a unique slug from the product name.
     */
    private function generateSlug(string $name): string
    {
        $baseSlug = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['-', 'a', 'e', 'i', 'o', 'u', 'n'], $name));
        $timestamp = now()->format('YmdHisu'); // Incluye microsegundos
        $random = $this->faker->numberBetween(10000, 99999);
        return $baseSlug . '-' . $timestamp . '-' . $random;
    }

    /**
     * Generate a unique SKU for the product.
     */
    private function generateSku(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 3) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        $timestamp = now()->format('Hisu'); // Incluye microsegundos
        $random = $this->faker->numberBetween(10000, 99999);
        return $initials . '-' . $timestamp . '-' . $random;
    }

    /**
     * Generate product description.
     */
    private function generateDescription(string $name, string $category): string
    {
        $descriptions = [
            'ropa-deportiva' => "Diseñado especialmente para entrenamientos intensos, este {$name} combina comodidad, estilo y funcionalidad. Fabricado con materiales de alta calidad que permiten libertad de movimiento y excelente transpirabilidad.",
            'accesorios' => "Complementa tu rutina de ejercicios con este {$name}. Diseñado para mejorar tu experiencia de entrenamiento con materiales duraderos y diseño ergonómico.",
            'equipamiento' => "Equipo profesional {$name} ideal para entrenamientos en casa o en el estudio. Fabricado con materiales de alta calidad para garantizar durabilidad y seguridad.",
            'nutricion' => "Suplemento nutricional {$name} formulado especialmente para deportistas y personas activas. Ingredientes de alta calidad para optimizar tu rendimiento y recuperación.",
            'bienestar' => "Producto de bienestar {$name} diseñado para complementar tu rutina de cuidado personal y recuperación post-entrenamiento.",
        ];

        return $descriptions[$category] ?? "Producto de alta calidad {$name} de la marca RSISTANC.";
    }

    /**
     * Generate short description.
     */
    private function generateShortDescription(string $name): string
    {
        return "Producto premium {$name} de RSISTANC. Calidad garantizada y diseño funcional.";
    }

    /**
     * Generate weight based on category.
     */
    private function generateWeight(string $category): int
    {
        $weights = [
            'ropa-deportiva' => $this->faker->numberBetween(150, 400),
            'accesorios' => $this->faker->numberBetween(50, 800),
            'equipamiento' => $this->faker->numberBetween(200, 2000),
            'nutricion' => $this->faker->numberBetween(500, 1500),
            'bienestar' => $this->faker->numberBetween(100, 500),
        ];

        return $weights[$category] ?? $this->faker->numberBetween(100, 1000);
    }

    /**
     * Generate dimensions based on category.
     */
    private function generateDimensions(string $category): array
    {
        return [
            'length' => $this->faker->numberBetween(10, 50),
            'width' => $this->faker->numberBetween(10, 40),
            'height' => $this->faker->numberBetween(2, 20),
        ];
    }

    /**
     * Generate product images.
     */
    private function generateImages(string $slug): array
    {
        return [
            "/images/products/{$slug}/main.jpg",
            "/images/products/{$slug}/detail1.jpg",
            "/images/products/{$slug}/detail2.jpg",
        ];
    }

    /**
     * Generate product features.
     */
    private function generateFeatures(string $category): array
    {
        $features = [
            'ropa-deportiva' => ['Transpirable', 'Secado rápido', 'Antibacterial', 'Elasticidad 4 vías'],
            'accesorios' => ['Duradero', 'Ergonómico', 'Fácil limpieza', 'Portátil'],
            'equipamiento' => ['Antideslizante', 'Resistente', 'Fácil almacenamiento', 'Seguro'],
            'nutricion' => ['Sin gluten', 'Sin azúcar añadida', 'Sabor natural', 'Fácil disolución'],
            'bienestar' => ['Natural', 'Hipoalergénico', 'Dermatológicamente probado', 'Vegano'],
        ];

        return $features[$category] ?? ['Calidad premium', 'Garantía RSISTANC'];
    }

    /**
     * Generate care instructions.
     */
    private function generateCareInstructions(string $category): string
    {
        $instructions = [
            'ropa-deportiva' => 'Lavar a máquina en agua fría. No usar suavizante. Secar al aire libre.',
            'accesorios' => 'Limpiar con paño húmedo. Secar completamente antes de guardar.',
            'equipamiento' => 'Limpiar después de cada uso. Almacenar en lugar seco.',
            'nutricion' => 'Conservar en lugar fresco y seco. Consumir antes de la fecha de vencimiento.',
            'bienestar' => 'Uso externo únicamente. Mantener fuera del alcance de los niños.',
        ];

        return $instructions[$category] ?? 'Seguir las instrucciones del fabricante.';
    }

    /**
     * Generate material composition.
     */
    private function generateMaterialComposition(string $category): string
    {
        $compositions = [
            'ropa-deportiva' => '80% Poliéster, 20% Elastano',
            'accesorios' => 'Materiales sintéticos de alta calidad',
            'equipamiento' => 'PVC libre de ftalatos / Caucho natural',
            'nutricion' => 'Ver etiqueta nutricional en el empaque',
            'bienestar' => 'Ingredientes naturales certificados',
        ];

        return $compositions[$category] ?? 'Materiales de alta calidad';
    }

    /**
     * Generate meta description.
     */
    private function generateMetaDescription(string $name): string
    {
        return "Compra {$name} en RSISTANC. Productos de alta calidad para tu entrenamiento. Envío gratis en compras mayores a S/150.";
    }

    /**
     * Generate nutritional info for nutrition products.
     */
    private function generateNutritionalInfo(): array
    {
        return [
            'calories_per_serving' => $this->faker->numberBetween(100, 300),
            'protein_g' => $this->faker->numberBetween(20, 35),
            'carbs_g' => $this->faker->numberBetween(5, 15),
            'fat_g' => $this->faker->numberBetween(1, 8),
            'fiber_g' => $this->faker->numberBetween(0, 5),
            'sugar_g' => $this->faker->numberBetween(0, 10),
            'sodium_mg' => $this->faker->numberBetween(50, 200),
        ];
    }

    /**
     * Generate ingredients for nutrition products.
     */
    private function generateIngredients(): array
    {
        return $this->faker->randomElements([
            'Proteína de suero',
            'Caseína',
            'Creatina',
            'BCAA',
            'Glutamina',
            'Vitamina C',
            'Vitamina D',
            'Magnesio',
            'Zinc',
            'Saborizante natural',
            'Stevia',
            'Lecitina de soja',
        ], $this->faker->numberBetween(3, 8));
    }

    /**
     * Generate allergens for nutrition products.
     */
    private function generateAllergens(): array
    {
        return $this->faker->randomElements([
            'Leche',
            'Soja',
            'Huevo',
            'Frutos secos',
            'Gluten',
        ], $this->faker->numberBetween(0, 3));
    }

    /**
     * Get product type based on category.
     */
    private function getProductType(string $categorySlug): string
    {
        return match ($categorySlug) {
            'nutricion' => $this->faker->randomElement(['shake', 'supplement']),
            'membresias' => 'service',
            default => 'merchandise',
        };
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product has discount.
     */
    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'] ?? 100;
            return [
                'discount_price' => $price * $this->faker->randomFloat(2, 0.7, 0.9),
            ];
        });
    }
}
