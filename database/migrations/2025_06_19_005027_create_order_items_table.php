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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->json('variant_info')->nullable()->comment('Información de variante seleccionada');
            $table->unsignedTinyInteger('quantity');
            $table->decimal('unit_price_soles', 8, 2);
            $table->decimal('total_price_soles', 8, 2);
            $table->json('customizations')->nullable()->comment('Personalizaciones del producto');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index('order_id');
            $table->index('product_id');
            $table->index('product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
