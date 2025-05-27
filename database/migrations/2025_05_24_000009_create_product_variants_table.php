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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku', 50)->unique()->comment('SKU único para cada variante');
            $table->string('variant_name', 100)->comment('Nombre descriptivo de la variante');
            $table->enum('size', ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', '3XL'])->nullable();
            $table->string('color', 50)->nullable();
            $table->string('material', 100)->nullable();
            $table->string('flavor', 50)->nullable()->comment('Para suplementos/batidos');
            $table->enum('intensity', ['light', 'medium', 'strong'])->nullable()->comment('Para productos con intensidad');
            $table->decimal('price_modifier', 8, 2)->default(0.00)->comment('Modificador sobre precio base');
            $table->decimal('cost_price', 8, 2)->nullable()->comment('Precio de costo para análisis');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('min_stock_alert')->default(5);
            $table->unsignedInteger('max_stock_capacity')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->string('dimensions_cm', 50)->nullable()->comment('LxWxH en cm');
            $table->string('barcode', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_default')->default(false)->comment('Variante por defecto del producto');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            // Índices
            $table->index(['product_id', 'is_active']);
            $table->index('sku');
            $table->index(['stock_quantity', 'min_stock_alert']);
            $table->index(['size', 'color']);
            $table->index(['product_id', 'is_active', 'stock_quantity']);
            $table->index(['product_id', 'is_default']);

            // Note: Check constraints will be handled at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
