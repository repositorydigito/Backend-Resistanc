<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductTag;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsAndOrdersSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛍️ Seeding products and orders...');

        DB::transaction(function () {
            // 1. Create products with variants
            $this->createProductsWithVariants();

            // 2. Create user packages
            $this->createUserPackages();

            // 3. Create orders
            $this->createOrders();

            // 4. Attach tags to products
            $this->attachTagsToProducts();
        });

        $this->command->info('🎉 Products and orders seeding completed!');
        $this->displaySummary();
    }

    /**
     * Create products with their variants.
     */
    private function createProductsWithVariants(): void
    {
        $this->command->info('📦 Creating products with variants...');

        $categories = ProductCategory::all();

        foreach ($categories as $category) {
            // Create 5-8 products per category
            $productCount = $this->faker->numberBetween(5, 8);

            for ($i = 0; $i < $productCount; $i++) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                ]);

                // Create variants for each product
                $this->createVariantsForProduct($product, $category->slug);

                $this->command->line("✅ Created product: {$product->name} with variants");
            }
        }
    }

    /**
     * Create variants for a specific product based on category.
     */
    private function createVariantsForProduct(Product $product, string $categorySlug): void
    {
        switch ($categorySlug) {
            case 'ropa-deportiva':
                // Clothing: size and color variants
                $this->createClothingVariants($product);
                break;

            case 'nutricion':
                // Nutrition: flavor and capacity variants
                $this->createNutritionVariants($product);
                break;

            case 'accesorios':
                // Accessories: color variants
                $this->createAccessoryVariants($product);
                break;

            default:
                // Default: basic variants
                $this->createBasicVariants($product);
                break;
        }
    }

    /**
     * Create clothing variants (size + color).
     */
    private function createClothingVariants(Product $product): void
    {
        $sizes = ['s', 'm', 'l', 'xl'];
        $colors = ['black', 'white', 'gray', 'navy'];

        foreach ($sizes as $index => $size) {
            ProductVariant::factory()->size($size)->create([
                'product_id' => $product->id,
                'is_default' => $index === 1, // Medium as default
            ]);
        }

        foreach ($colors as $index => $color) {
            ProductVariant::factory()->color($color)->create([
                'product_id' => $product->id,
                'is_default' => $index === 0, // Black as default
            ]);
        }
    }

    /**
     * Create nutrition variants (flavor + capacity).
     */
    private function createNutritionVariants(Product $product): void
    {
        // Create flavor variants
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => $this->generateVariantSku(),
            'variant_name' => 'Vainilla',
            'flavor' => 'vanilla',
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => $this->generateVariantSku(),
            'variant_name' => 'Chocolate',
            'flavor' => 'chocolate',
        ]);

        // Create capacity variants
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => $this->generateVariantSku(),
            'variant_name' => '1 Kilogramo',
            'weight_grams' => 1000,
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => $this->generateVariantSku(),
            'variant_name' => '2 Kilogramos',
            'weight_grams' => 2000,
            'price_modifier' => 80.00,
        ]);
    }

    /**
     * Create accessory variants (color only).
     */
    private function createAccessoryVariants(Product $product): void
    {
        $colors = ['black', 'blue', 'pink'];

        foreach ($colors as $index => $color) {
            ProductVariant::factory()->color($color)->create([
                'product_id' => $product->id,
                'is_default' => $index === 0,
            ]);
        }
    }

    /**
     * Create basic variants.
     */
    private function createBasicVariants(Product $product): void
    {
        // Just create one default variant
        ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'sku' => $this->generateVariantSku(),
            'variant_name' => 'Estándar',
        ]);
    }

    /**
     * Generate unique variant SKU.
     */
    private function generateVariantSku(): string
    {
        $timestamp = now()->format('Hisu');
        $random = $this->faker->numberBetween(1000, 9999);
        return 'VAR-' . $timestamp . '-' . $random;
    }

    /**
     * Create user packages.
     */
    private function createUserPackages(): void
    {
        $this->command->info('📋 Creating user packages...');

        $users = User::limit(20)->get(); // Get first 20 users

        foreach ($users as $user) {
            // 80% of users have at least one package
            if ($this->faker->boolean(80)) {
                // Create 1-3 packages per user
                $packageCount = $this->faker->numberBetween(1, 3);

                for ($i = 0; $i < $packageCount; $i++) {
                    UserPackage::factory()->create([
                        'user_id' => $user->id,
                    ]);
                }
            }
        }

        $this->command->line("✅ Created " . UserPackage::count() . " user packages");
    }

    /**
     * Create orders.
     */
    private function createOrders(): void
    {
        $this->command->info('🛒 Creating orders...');

        $users = User::limit(15)->get(); // Get first 15 users

        foreach ($users as $user) {
            // 70% of users have made orders
            if ($this->faker->boolean(70)) {
                // Create 1-5 orders per user
                $orderCount = $this->faker->numberBetween(1, 5);

                for ($i = 0; $i < $orderCount; $i++) {
                    $orderType = $this->faker->randomElement(['recent', 'delivered', 'pending', 'cancelled']);

                    switch ($orderType) {
                        case 'recent':
                            Order::factory()->recent()->create(['user_id' => $user->id]);
                            break;
                        case 'delivered':
                            Order::factory()->delivered()->create(['user_id' => $user->id]);
                            break;
                        case 'pending':
                            Order::factory()->pending()->create(['user_id' => $user->id]);
                            break;
                        case 'cancelled':
                            Order::factory()->cancelled()->create(['user_id' => $user->id]);
                            break;
                    }
                }
            }
        }

        // Create some high-value orders
        Order::factory(5)->highValue()->delivered()->create();

        $this->command->line("✅ Created " . Order::count() . " orders");
    }

    /**
     * Attach tags to products.
     */
    private function attachTagsToProducts(): void
    {
        $this->command->info('🏷️ Attaching tags to products...');

        $products = Product::all();
        $tags = ProductTag::all();

        foreach ($products as $product) {
            // Attach 1-4 random tags to each product
            $tagCount = $this->faker->numberBetween(1, 4);
            $randomTags = $tags->random($tagCount);

            $product->tags()->attach($randomTags->pluck('id'));
        }

        $this->command->line("✅ Attached tags to products");
    }

    /**
     * Display a summary of created data.
     */
    private function displaySummary(): void
    {
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Products', Product::count()],
                ['Product Variants', ProductVariant::count()],
                ['User Packages', UserPackage::count()],
                ['Orders', Order::count()],
            ]
        );

        $this->command->info('📈 Products summary:');
        $this->command->line('• Featured products: ' . Product::where('is_featured', true)->count());
        $this->command->line('• Active products: ' . Product::where('status', 'active')->count());
        $this->command->line('• Products with discounts: ' . Product::whereNotNull('compare_price_soles')->count());

        $this->command->info('📈 Orders summary:');
        $this->command->line('• Delivered orders: ' . Order::where('status', 'delivered')->count());
        $this->command->line('• Pending orders: ' . Order::where('status', 'pending')->count());
        $this->command->line('• Cancelled orders: ' . Order::where('status', 'cancelled')->count());
        $this->command->line('• Total order value: S/ ' . number_format((float) Order::sum('total_amount_soles'), 2));
    }


}
