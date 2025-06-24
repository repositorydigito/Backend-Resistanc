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
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->nullable()->unique();
            $table->decimal('price_soles', 8, 2);
            $table->decimal('cost_price_soles', 8, 2)->nullable();
            $table->decimal('compare_price_soles', 8, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('min_stock_alert')->default(5);
            $table->boolean('is_active')->default(true);

            $table->string('main_image')->nullable(); // Imagen principal del producto
            $table->json('images')->nullable(); // Almacena imágenes específicas de la variante

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
