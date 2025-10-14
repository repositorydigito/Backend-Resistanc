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
        Schema::create('juice_order_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('juice_order_id')
                ->constrained('juice_orders')
                ->onDelete('cascade')
                ->comment('ID del pedido asociado');

            $table->foreignId('drink_id')
                ->constrained('drinks')
                ->onDelete('cascade');

            $table->integer('quantity');

            // Datos históricos de la bebida (como texto para integridad)
            $table->string('drink_name', 255)->nullable()->comment('Nombre completo de la bebida al momento del pedido');
            $table->json('drink_combination')->nullable()->comment('Combinación de ingredientes al momento del pedido');

            // Campos de precios (como texto para integridad)
            $table->decimal('unit_price_soles', 10, 2)->default(0.00)->comment('Precio unitario al momento del pedido');
            $table->decimal('total_price_soles', 10, 2)->default(0.00)->comment('Precio total del item');

            // Información adicional
            $table->text('special_instructions')->nullable()->comment('Instrucciones especiales para esta bebida');
            $table->json('ingredients_info')->nullable()->comment('Información detallada de ingredientes');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juice_order_details');
    }
};
