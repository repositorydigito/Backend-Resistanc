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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number', 20)->unique()->comment('Número de orden único');
            $table->enum('order_type', ['purchase', 'booking_extras', 'subscription', 'gift'])->default('purchase')->comment('Tipo de orden');
            $table->decimal('subtotal_soles', 10, 2)->comment('Subtotal en soles');
            $table->decimal('tax_amount_soles', 8, 2)->default(0.00)->comment('Monto del impuesto en soles');
            $table->decimal('shipping_amount_soles', 8, 2)->default(0.00)->comment('Monto del envío en soles');
            $table->decimal('discount_amount_soles', 8, 2)->default(0.00)->comment('Monto del descuento en soles');
            $table->decimal('total_amount_soles', 10, 2)->comment('Monto total en soles');
            $table->char('currency', 3)->default('PEN')->comment('Código de moneda');

            $table->enum('status', ['pending', 'confirmed', 'processing', 'preparing', 'ready', 'delivered', 'cancelled', 'refunded'])->default('pending')->comment('Estado de la orden');
            $table->enum('payment_status', ['pending', 'authorized', 'paid', 'partially_paid', 'failed', 'refunded'])->default('pending')->comment('Estado del pago');

            $table->string('payment_method_name', 255)->nullable()->comment('Nombre del método de pago');

            $table->json('items')->comment('Lista de productos comprados');

            // Delivery
            $table->enum('delivery_method', ['pickup', 'delivery', 'digital'])->nullable()->default('pickup')->comment('Método de entrega');
            $table->date('delivery_date')->nullable()->comment('Fecha de entrega');
            $table->string('delivery_time_slot', 50)->nullable()->comment('Franja horaria de entrega');
            $table->json('delivery_address')->nullable()->comment('Dirección de entrega');
            $table->text('special_instructions')->nullable()->comment('Instrucciones especiales');
            $table->string('promocode_used', 50)->nullable()->comment('Código promocional utilizado');
            $table->text('notes')->nullable()->comment('Notas');
            // Delivery



            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_number');
            $table->index('created_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
