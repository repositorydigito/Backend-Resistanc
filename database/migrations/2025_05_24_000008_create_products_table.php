<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('Nombre del producto');
            $table->string('slug')->unique()->comment('Slug del producto');

            $table->string('sku', 50)->unique()->comment('SKU del producto');
            $table->text('description')->nullable()->comment('Descripción del producto');
            $table->string('short_description', 500)->nullable()->comment('Descripción corta del producto');

            $table->decimal('price_soles', 8, 2)->nullable()->comment('Precio del producto en soles'); // Solo si no tiene variantes
            $table->decimal('cost_price_soles', 8, 2)->nullable()->comment('Precio de costo del producto en soles');
            $table->decimal('compare_price_soles', 8, 2)->nullable()->comment('Precio de comparación del producto en soles');

            $table->unsignedInteger('min_stock_alert')->default(5)->comment('Cantidad mínima para alerta de stock'); // Para alerta si usa variantes
            $table->unsignedInteger('stock_quantity')->default(0)->comment('Cantidad de stock del producto'); // Solo si no tiene variantes
            $table->unsignedInteger('weight_grams')->nullable()->comment('Peso del producto en gramos');
            $table->json('dimensions')->nullable()->comment('Dimensiones del producto');
            $table->json('images')->nullable()->comment('Imágenes del producto');
            $table->string('img_url')->nullable()->comment('Imagen principal del producto');

            $table->json('nutritional_info')->nullable()->comment('Información nutricional del producto');
            $table->json('ingredients')->nullable()->comment('Ingredientes del producto');
            $table->json('allergens')->nullable()->comment('Alérgenos del producto');

            // $table->enum('product_type', ['shake', 'supplement', 'merchandise', 'service', 'gift_card']);

            $table->boolean('requires_variants')->default(false)->comment('Si necesita variantes (tallas, colores)');
            $table->boolean('is_virtual')->default(false)->comment('Si es un producto virtual (sin envío físico)');
            $table->boolean('is_featured')->default(false)->comment('Si es un producto destacado');
            $table->boolean('is_available_for_booking')->default(false)->comment('Si está disponible para reservas');

            // Producto de cupon
            $table->boolean('is_cupon')->default(false)->comment('Si es cupon de descuento');
            $table->longText('url_cupon_code')->nullable()->comment('Url del cupón si es un producto.');
            // Fin Producto de cupon

            $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active')->comment('Estado del producto');

            $table->string('meta_title')->nullable()->comment('Título SEO del producto');
            $table->string('meta_description', 500)->nullable()->comment('Descripción SEO del producto');

            // Relaciones
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->foreignId('product_brand_id')->nullable()->constrained('product_brands')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
