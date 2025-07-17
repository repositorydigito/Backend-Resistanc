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
        // no utilizado
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->enum('transaction_type', [
                'package_purchase',
                'product_order',
                'subscription_payment',
                'refund',
                'chargeback',
                'fee'
            ])->comment('Tipo de transacción');

            $table->decimal('amount_soles', 10, 2)->comment('Monto de la transacción en soles');
            $table->char('currency', 3)->default('PEN')->comment('Código de moneda');
            $table->decimal('exchange_rate', 10, 4)->nullable()->comment('Tasa de cambio');

            // Gateway
            $table->enum('gateway_provider', ['culqi', 'niubiz', 'paypal', 'stripe', 'izipay', 'payu'])->nullable()->comment('Proveedor del gateway de pago');
            $table->string('gateway_transaction_id')->nullable()->comment('ID de transacción del gateway');
            $table->json('gateway_response')->nullable()->comment('Respuesta completa del gateway');
            $table->string('confirmation_code', 100)->nullable()->comment('Código de confirmación');
            $table->string('reference_number', 100)->nullable()->comment('Número de referencia');

            // Estado de pago
            $table->enum('payment_status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
                'refunded',
                'disputed'
            ])->default('pending')->comment('Estado del pago');

            $table->text('refund_reason')->nullable()->comment('Razón del reembolso');
            $table->text('failure_reason')->nullable()->comment('Razón del fallo');
            $table->text('notes')->nullable()->comment('Notas');

            $table->timestamp('processed_at')->nullable()->comment('Fecha y hora de procesamiento');
            $table->timestamp('reconciled_at')->nullable()->comment('Fecha y hora de conciliación');

            $table->json('fees')->nullable()->comment('Comisiones y fees aplicados');
            $table->json('metadata')->nullable()->comment('Metadata adicional del pago');

            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained('user_payment_methods')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('user_package_id')->nullable()->constrained('user_packages')->onDelete('set null');

            // Índices
            $table->index(['user_id', 'payment_status'], 'idx_transactions_user');
            $table->index('gateway_transaction_id', 'idx_transactions_gateway');
            $table->index('payment_status', 'idx_transactions_status');
            $table->index('transaction_type', 'idx_transactions_type');
            $table->index('created_at', 'idx_transactions_date');

            $table->timestamps(); // created_at y updated_at
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
