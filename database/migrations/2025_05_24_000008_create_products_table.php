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
            // $table->id();
            // $table->string('name');
            // $table->string('slug')->unique();
            // $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            // $table->string('sku', 50)->unique();
            // $table->text('description')->nullable();
            // $table->string('short_description', 500)->nullable();
            // $table->decimal('price_soles', 8, 2);
            // $table->decimal('cost_price_soles', 8, 2)->nullable();
            // $table->decimal('compare_price_soles', 8, 2)->nullable()->comment('Precio de comparación/original');
            // $table->unsignedInteger('stock_quantity')->default(0);
            // $table->unsignedInteger('min_stock_alert')->default(5);
            // $table->unsignedInteger('weight_grams')->nullable();
            // $table->json('dimensions')->nullable()->comment('Dimensiones del producto');
            // $table->json('images')->nullable()->comment('URLs de imágenes del producto');
            // $table->json('nutritional_info')->nullable()->comment('Para batidos y suplementos');
            // $table->json('ingredients')->nullable();
            // $table->json('allergens')->nullable();
            // $table->enum('product_type', ['shake', 'supplement', 'merchandise', 'service', 'gift_card']);
            // $table->boolean('requires_variants')->default(false)->comment('Si necesita variantes (tallas, colores)');
            // $table->boolean('is_virtual')->default(false);
            // $table->boolean('is_featured')->default(false);
            // $table->boolean('is_available_for_booking')->default(false)->comment('Si se puede agregar en reservas');
            // $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active');
            // $table->string('meta_title')->nullable();
            // $table->string('meta_description', 500)->nullable();
            // $table->timestamps();

            // // Índices
            // $table->index(['category_id', 'status']);
            // $table->index(['product_type', 'status']);
            // $table->index('sku');
            // $table->index(['is_featured', 'status']);
            // $table->index(['is_available_for_booking', 'status']);
            // $table->index(['stock_quantity', 'min_stock_alert']);

            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            $table->string('sku', 50)->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();

            $table->decimal('price_soles', 8, 2)->nullable(); // Solo si no tiene variantes
            $table->decimal('cost_price_soles', 8, 2)->nullable();
            $table->decimal('compare_price_soles', 8, 2)->nullable();

            $table->unsignedInteger('min_stock_alert')->default(5); // Para alerta si usa variantes
             $table->unsignedInteger('stock_quantity')->default(0); // Solo si no tiene variantes
            $table->unsignedInteger('weight_grams')->nullable();
            $table->json('dimensions')->nullable();
            $table->json('images')->nullable();
            $table->string('img_url')->nullable(); // Imagen principal del producto

            $table->json('nutritional_info')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('allergens')->nullable();

            // $table->enum('product_type', ['shake', 'supplement', 'merchandise', 'service', 'gift_card']);

            $table->boolean('requires_variants')->default(false)->comment('Si necesita variantes (tallas, colores)');
            $table->boolean('is_virtual')->default(false)->comment('Si es un producto virtual (sin envío físico)');
            $table->boolean('is_featured')->default(false)->comment('Si es un producto destacado');
            $table->boolean('is_available_for_booking')->default(false)->comment('Si está disponible para reservas');

            // Producto de cupon
            $table->boolean('is_cupon')->default(false)->comment('Si es cupon de descuento');
            $table->longText('url_cupon_code')->nullable()->comment('Url del cupón si es un producto.');
            // Fin Producto de cupon

            $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active');

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

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
