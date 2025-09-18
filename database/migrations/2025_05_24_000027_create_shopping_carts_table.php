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
        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->id();

            $table->string('session_id', 100)->nullable()->comment('ID de sesión del carrito de compras');
            $table->decimal('total_amount', 10, 2)->default(0.00)->comment('Monto total del carrito de compras');
            $table->unsignedInteger('item_count')->default(0)->comment('Cantidad total de artículos en el carrito de compras');

            $table->enum('status', ['active', 'completed', 'abandoned', 'converted'])->default('active')->comment('Estado del carrito de compras');

            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index('session_id');
            $table->index('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_carts');
    }
};
