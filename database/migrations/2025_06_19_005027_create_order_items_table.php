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

            $table->unsignedTinyInteger('quantity')->comment('Cantidad del producto en la orden');
            $table->decimal('unit_price', 8, 2)->comment('Precio unitario del producto');
            $table->decimal('total_price', 8, 2)->comment('Precio total del producto');

            // Relaciones
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('set null');

            // Índices
            $table->index('order_id');
            $table->index('product_id');
            $table->index('product_variant_id');

            $table->timestamps();
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
