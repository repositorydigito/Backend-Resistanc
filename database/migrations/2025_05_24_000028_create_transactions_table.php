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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained('user_payment_methods')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('user_package_id')->nullable()->constrained('user_packages')->onDelete('set null');
            $table->string('transaction_code', 30)->unique();
            $table->enum('transaction_type', ['package_purchase', 'product_purchase', 'subscription_payment', 'refund', 'partial_refund', 'cancellation_fee']);
            $table->decimal('amount_soles', 10, 2);
            $table->char('currency', 3)->default('PEN');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'cash']);
            $table->string('payment_provider', 50)->nullable()->comment('visa, mastercard, yape, plin, etc.');
            $table->string('external_transaction_id')->nullable()->comment('ID del proveedor de pago');
            $table->string('authorization_code', 50)->nullable();
            $table->decimal('processing_fee', 8, 2)->default(0.00);
            $table->json('payment_details')->nullable()->comment('Detalles específicos del método de pago');
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['transaction_type', 'status']);
            $table->index('status');
            $table->index('transaction_code');
            $table->index('external_transaction_id');
            $table->index('processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
