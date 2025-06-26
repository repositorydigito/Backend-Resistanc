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
            // $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');

            $table->enum('transaction_type', [
                'package_purchase',
                'product_order',
                'subscription_payment',
                'refund',
                'chargeback',
                'fee'
            ]);

            $table->decimal('amount_soles', 10, 2);
            $table->char('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 10, 4)->nullable();

            // Gateway
            $table->enum('gateway_provider', ['culqi', 'niubiz', 'paypal', 'stripe', 'izipay', 'payu'])->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable()->comment('Respuesta completa del gateway');
            $table->string('confirmation_code', 100)->nullable();
            $table->string('reference_number', 100)->nullable();

            // Estado de pago
            $table->enum('payment_status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'disputed'
            ])->default('pending');

            $table->text('refund_reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();

            $table->json('fees')->nullable()->comment('Comisiones y fees aplicados');
            $table->json('metadata')->nullable()->comment('Metadata adicional del pago');

            $table->timestamps(); // created_at y updated_at

            // Índices
            $table->index(['user_id', 'payment_status'], 'idx_transactions_user');
            $table->index('gateway_transaction_id', 'idx_transactions_gateway');
            $table->index('payment_status', 'idx_transactions_status');
            $table->index('transaction_type', 'idx_transactions_type');
            $table->index('created_at', 'idx_transactions_date');
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
