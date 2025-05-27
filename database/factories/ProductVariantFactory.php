<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variantTypes = ['size', 'color', 'size_color', 'flavor', 'capacity'];
        $variantType = $this->faker->randomElement($variantTypes);

        $variantData = $this->generateVariantData($variantType);

        // Calculate price variation
        $basePriceAdjustment = $this->faker->randomFloat(2, -20.00, 50.00);

        return [
            'product_id' => Product::factory(),
            'sku' => $this->generateUniqueSku(),
            'variant_name' => $variantData['display_name'],
            'size' => $variantType === 'size' || $variantType === 'size_color' ? $this->getValidSize($variantData['value']) : null,
            'color' => $variantType === 'color' || $variantType === 'size_color' ? $variantData['value'] : null,
            'material' => $this->faker->randomElement(['algodón', 'poliéster', 'spandex', 'nylon', null]),
            'flavor' => $variantType === 'flavor' ? $variantData['value'] : null,
            'intensity' => $this->faker->randomElement(['light', 'medium', 'strong', null]),
            'price_modifier' => $basePriceAdjustment,
            'cost_price' => $this->faker->randomFloat(2, 50.00, 200.00),
            'stock_quantity' => $this->faker->numberBetween(0, 50),
            'min_stock_alert' => $this->faker->numberBetween(1, 10),
            'max_stock_capacity' => $this->faker->numberBetween(100, 500),
            'weight_grams' => $this->faker->numberBetween(100, 2000),
            'dimensions_cm' => $this->generateDimensions(),
            'barcode' => $this->faker->ean13(),
            'is_active' => $this->faker->boolean(95), // 95% are active
            'is_featured' => $this->faker->boolean(20), // 20% are featured
            'is_default' => $this->faker->boolean(20), // 20% are default variants
            'sort_order' => $this->faker->numberBetween(1, 100),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate variant data based on type.
     */
    private function generateVariantData(string $variantType): array
    {
        switch ($variantType) {
            case 'size':
                return $this->generateSizeVariant();
            case 'color':
                return $this->generateColorVariant();
            case 'size_color':
                return $this->generateSizeColorVariant();
            case 'flavor':
                return $this->generateFlavorVariant();
            case 'capacity':
                return $this->generateCapacityVariant();
            default:
                return $this->generateSizeVariant();
        }
    }

    /**
     * Generate size variant data.
     */
    private function generateSizeVariant(): array
    {
        $sizes = [
            ['value' => 'xs', 'display' => 'Extra Small (XS)', 'sku' => 'XS'],
            ['value' => 's', 'display' => 'Small (S)', 'sku' => 'S'],
            ['value' => 'm', 'display' => 'Medium (M)', 'sku' => 'M'],
            ['value' => 'l', 'display' => 'Large (L)', 'sku' => 'L'],
            ['value' => 'xl', 'display' => 'Extra Large (XL)', 'sku' => 'XL'],
            ['value' => 'xxl', 'display' => 'Double Extra Large (XXL)', 'sku' => 'XXL'],
        ];

        $size = $this->faker->randomElement($sizes);

        return [
            'value' => $size['value'],
            'display_name' => $size['display'],
            'sku_suffix' => $size['sku'],
        ];
    }

    /**
     * Generate color variant data.
     */
    private function generateColorVariant(): array
    {
        $colors = [
            ['value' => 'black', 'display' => 'Negro', 'sku' => 'BLK'],
            ['value' => 'white', 'display' => 'Blanco', 'sku' => 'WHT'],
            ['value' => 'gray', 'display' => 'Gris', 'sku' => 'GRY'],
            ['value' => 'navy', 'display' => 'Azul Marino', 'sku' => 'NVY'],
            ['value' => 'pink', 'display' => 'Rosa', 'sku' => 'PNK'],
            ['value' => 'purple', 'display' => 'Morado', 'sku' => 'PRP'],
            ['value' => 'red', 'display' => 'Rojo', 'sku' => 'RED'],
            ['value' => 'blue', 'display' => 'Azul', 'sku' => 'BLU'],
            ['value' => 'green', 'display' => 'Verde', 'sku' => 'GRN'],
        ];

        $color = $this->faker->randomElement($colors);

        return [
            'value' => $color['value'],
            'display_name' => $color['display'],
            'sku_suffix' => $color['sku'],
        ];
    }

    /**
     * Generate size and color variant data.
     */
    private function generateSizeColorVariant(): array
    {
        $sizeData = $this->generateSizeVariant();
        $colorData = $this->generateColorVariant();

        return [
            'value' => $sizeData['value'] . '_' . $colorData['value'],
            'display_name' => $sizeData['display_name'] . ' - ' . $colorData['display_name'],
            'sku_suffix' => $sizeData['sku_suffix'] . '-' . $colorData['sku_suffix'],
        ];
    }

    /**
     * Generate flavor variant data.
     */
    private function generateFlavorVariant(): array
    {
        $flavors = [
            ['value' => 'vanilla', 'display' => 'Vainilla', 'sku' => 'VAN'],
            ['value' => 'chocolate', 'display' => 'Chocolate', 'sku' => 'CHO'],
            ['value' => 'strawberry', 'display' => 'Fresa', 'sku' => 'STR'],
            ['value' => 'banana', 'display' => 'Plátano', 'sku' => 'BAN'],
            ['value' => 'coconut', 'display' => 'Coco', 'sku' => 'COC'],
            ['value' => 'mango', 'display' => 'Mango', 'sku' => 'MNG'],
            ['value' => 'berry_mix', 'display' => 'Mix de Berries', 'sku' => 'BRY'],
            ['value' => 'unflavored', 'display' => 'Sin Sabor', 'sku' => 'UNF'],
        ];

        $flavor = $this->faker->randomElement($flavors);

        return [
            'value' => $flavor['value'],
            'display_name' => $flavor['display'],
            'sku_suffix' => $flavor['sku'],
        ];
    }

    /**
     * Generate capacity variant data.
     */
    private function generateCapacityVariant(): array
    {
        $capacities = [
            ['value' => '250ml', 'display' => '250ml', 'sku' => '250'],
            ['value' => '500ml', 'display' => '500ml', 'sku' => '500'],
            ['value' => '750ml', 'display' => '750ml', 'sku' => '750'],
            ['value' => '1l', 'display' => '1 Litro', 'sku' => '1L'],
            ['value' => '1kg', 'display' => '1 Kilogramo', 'sku' => '1KG'],
            ['value' => '2kg', 'display' => '2 Kilogramos', 'sku' => '2KG'],
            ['value' => '5kg', 'display' => '5 Kilogramos', 'sku' => '5KG'],
        ];

        $capacity = $this->faker->randomElement($capacities);

        return [
            'value' => $capacity['value'],
            'display_name' => $capacity['display'],
            'sku_suffix' => $capacity['sku'],
        ];
    }

    /**
     * Generate variant images.
     */
    private function generateVariantImages(string $variantValue): array
    {
        $slug = str_replace(['_', ' '], '-', strtolower($variantValue));

        return [
            "/images/products/variants/{$slug}/main.jpg",
            "/images/products/variants/{$slug}/detail.jpg",
        ];
    }

    /**
     * Generate variant attributes.
     */
    private function generateVariantAttributes(string $variantType, string $variantValue): array
    {
        $baseAttributes = [
            'variant_type' => $variantType,
            'variant_value' => $variantValue,
        ];

        switch ($variantType) {
            case 'size':
                return array_merge($baseAttributes, [
                    'size_category' => $this->getSizeCategory($variantValue),
                    'fit_type' => $this->faker->randomElement(['regular', 'slim', 'relaxed']),
                ]);

            case 'color':
                return array_merge($baseAttributes, [
                    'color_family' => $this->getColorFamily($variantValue),
                    'hex_code' => $this->getColorHex($variantValue),
                ]);

            case 'flavor':
                return array_merge($baseAttributes, [
                    'flavor_category' => $this->getFlavorCategory($variantValue),
                    'sweetness_level' => $this->faker->numberBetween(1, 5),
                ]);

            case 'capacity':
                return array_merge($baseAttributes, [
                    'unit_type' => $this->getUnitType($variantValue),
                    'serving_size' => $this->getServingSize($variantValue),
                ]);

            default:
                return $baseAttributes;
        }
    }

    /**
     * Get size category.
     */
    private function getSizeCategory(string $size): string
    {
        return match ($size) {
            'xs', 's' => 'small',
            'm', 'l' => 'medium',
            'xl', 'xxl' => 'large',
            default => 'medium',
        };
    }

    /**
     * Get color family.
     */
    private function getColorFamily(string $color): string
    {
        return match ($color) {
            'black', 'white', 'gray' => 'neutral',
            'red', 'pink' => 'warm',
            'blue', 'navy', 'purple' => 'cool',
            'green' => 'natural',
            default => 'other',
        };
    }

    /**
     * Get color hex code.
     */
    private function getColorHex(string $color): string
    {
        return match ($color) {
            'black' => '#000000',
            'white' => '#FFFFFF',
            'gray' => '#808080',
            'navy' => '#000080',
            'pink' => '#FFC0CB',
            'purple' => '#800080',
            'red' => '#FF0000',
            'blue' => '#0000FF',
            'green' => '#008000',
            default => '#CCCCCC',
        };
    }

    /**
     * Get flavor category.
     */
    private function getFlavorCategory(string $flavor): string
    {
        return match ($flavor) {
            'vanilla', 'chocolate' => 'classic',
            'strawberry', 'banana', 'mango' => 'fruit',
            'coconut' => 'tropical',
            'berry_mix' => 'berry',
            'unflavored' => 'neutral',
            default => 'other',
        };
    }

    /**
     * Get unit type.
     */
    private function getUnitType(string $capacity): string
    {
        if (str_contains($capacity, 'ml') || str_contains($capacity, 'l')) {
            return 'volume';
        }
        if (str_contains($capacity, 'kg') || str_contains($capacity, 'g')) {
            return 'weight';
        }
        return 'other';
    }

    /**
     * Get serving size.
     */
    private function getServingSize(string $capacity): string
    {
        return match ($capacity) {
            '250ml' => '1 serving',
            '500ml' => '2 servings',
            '1l' => '4 servings',
            '1kg' => '30 servings',
            '2kg' => '60 servings',
            '5kg' => '150 servings',
            default => '1 serving',
        };
    }

    /**
     * Generate unique SKU.
     */
    private function generateUniqueSku(): string
    {
        $timestamp = now()->format('Hisu');
        $random = $this->faker->numberBetween(1000, 9999);
        return 'VAR-' . $timestamp . '-' . $random;
    }

    /**
     * Get valid size from enum.
     */
    private function getValidSize(string $size): string
    {
        $validSizes = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', '3XL'];
        $upperSize = strtoupper($size);

        return in_array($upperSize, $validSizes) ? $upperSize : 'M';
    }

    /**
     * Generate dimensions.
     */
    private function generateDimensions(): string
    {
        $length = $this->faker->numberBetween(10, 50);
        $width = $this->faker->numberBetween(10, 40);
        $height = $this->faker->numberBetween(5, 30);

        return "{$length}x{$width}x{$height}";
    }

    /**
     * Indicate that the variant is default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Indicate that the variant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a size variant.
     */
    public function size(string $size = null): static
    {
        $sizeData = $size ?
            ['value' => $size, 'display_name' => strtoupper($size), 'sku_suffix' => strtoupper($size)] :
            $this->generateSizeVariant();

        return $this->state(fn (array $attributes) => [
            'variant_name' => $sizeData['display_name'],
            'size' => $this->getValidSize($sizeData['value']),
            'sku' => $this->generateUniqueSku(),
        ]);
    }

    /**
     * Create a color variant.
     */
    public function color(string $color = null): static
    {
        $colorData = $color ?
            ['value' => $color, 'display_name' => ucfirst($color), 'sku_suffix' => strtoupper(substr($color, 0, 3))] :
            $this->generateColorVariant();

        return $this->state(fn (array $attributes) => [
            'variant_name' => $colorData['display_name'],
            'color' => $colorData['value'],
            'sku' => $this->generateUniqueSku(),
        ]);
    }
}
