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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('order_number', 20)->unique();
            $table->enum('order_type', ['purchase', 'booking_extras', 'subscription', 'gift'])->default('purchase');
            $table->decimal('subtotal_soles', 10, 2);
            $table->decimal('tax_amount_soles', 8, 2)->default(0.00);
            $table->decimal('shipping_amount_soles', 8, 2)->default(0.00);
            $table->decimal('discount_amount_soles', 8, 2)->default(0.00);
            $table->decimal('total_amount_soles', 10, 2);
            $table->char('currency', 3)->default('PEN');
            $table->enum('status', ['pending', 'confirmed', 'processing', 'preparing', 'ready', 'delivered', 'cancelled', 'refunded'])->default('pending');
            $table->enum('payment_status', ['pending', 'authorized', 'paid', 'partially_paid', 'failed', 'refunded'])->default('pending');
            $table->enum('delivery_method', ['pickup', 'delivery', 'digital'])->default('pickup');
            $table->date('delivery_date')->nullable();
            $table->string('delivery_time_slot', 50)->nullable();
            $table->json('delivery_address')->nullable();
            $table->text('special_instructions')->nullable();
            $table->string('promocode_used', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes');
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_number');
            $table->index('created_at');
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
