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
        Schema::create('juice_orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique()->comment('Número de pedido único');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')->comment('ID del usuario que realiza el pedido');

            // Datos históricos del usuario (como texto para integridad)
            $table->string('user_name', 255)->nullable()->comment('Nombre del usuario al momento del pedido');
            $table->string('user_email', 255)->nullable()->comment('Email del usuario al momento del pedido');

            // Campos de precios y montos
            $table->decimal('subtotal_soles', 10, 2)->default(0.00)->comment('Subtotal en soles');
            $table->decimal('tax_amount_soles', 10, 2)->default(0.00)->comment('Monto de impuestos en soles');
            $table->decimal('discount_amount_soles', 10, 2)->default(0.00)->comment('Monto de descuento en soles');
            $table->decimal('total_amount_soles', 10, 2)->default(0.00)->comment('Monto total en soles');
            $table->string('currency', 3)->default('PEN')->comment('Moneda del pedido');

            // Estados del pedido
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'])->default('pending')->comment('Estado del pedido');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->comment('Estado del pago');

            // Información de entrega
            $table->enum('delivery_method', ['pickup', 'delivery'])->default('pickup')->comment('Método de entrega');
            $table->text('special_instructions')->nullable()->comment('Instrucciones especiales del pedido');
            $table->text('notes')->nullable()->comment('Notas adicionales del pedido');

            // Información del método de pago (como texto para integridad)
            $table->string('payment_method_name', 255)->nullable()->comment('Nombre del método de pago al momento del pedido');



            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_customer_id')->nullable();


            // Timestamps de estados
            $table->timestamp('estimated_ready_at')->nullable()->comment('Tiempo estimado de preparación');
            $table->timestamp('confirmed_at')->nullable()->comment('Momento de confirmación del pedido');
            $table->timestamp('preparing_at')->nullable()->comment('Momento en que comenzó la preparación');
            $table->timestamp('ready_at')->nullable()->comment('Momento en que el pedido estuvo listo');
            $table->timestamp('delivered_at')->nullable()->comment('Momento de entrega del pedido');

            $table->boolean('is_membership_redeem')
                ->default(false)

                ->comment('Indica si el pedido proviene de un canje de membresía');

            $table->foreignId('user_membership_id')
                ->nullable()

                ->constrained('user_memberships')
                ->nullOnDelete()
                ->comment('Membresía del usuario utilizada para el canje');

            $table->unsignedInteger('redeemed_shakes_quantity')
                ->default(0)
                ->comment('Cantidad de shakes canjeados en este pedido');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juice_orders');
    }
};
