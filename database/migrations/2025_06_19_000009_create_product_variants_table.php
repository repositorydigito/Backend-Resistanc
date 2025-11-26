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
            $table->string('sku')->nullable()->unique()->comment('Codigo del producto');
            $table->decimal('price_soles', 8, 2)->comment('Precio en soles');
            $table->decimal('cost_price_soles', 8, 2)->nullable()->comment('Precio de costo en soles');
            $table->decimal('compare_price_soles', 8, 2)->nullable()->comment('Precio de comparación en soles');
            $table->unsignedInteger('stock_quantity')->default(0)->comment('Cantidad de stock');
            $table->unsignedInteger('min_stock_alert')->default(5)->comment('Alerta de stock mínimo');
            $table->boolean('is_active')->default(true)->comment('Indica si la variante está activa');

            $table->string('main_image')->nullable()->comment('Imagen principal del producto');
            $table->json('images')->nullable()->comment('Almacena imágenes específicas de la variante');

            // Relaciones
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->timestamps();
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
