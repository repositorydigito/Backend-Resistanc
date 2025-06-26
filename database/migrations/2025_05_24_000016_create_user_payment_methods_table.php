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
        Schema::create('user_payment_methods', function (Blueprint $table) {

            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->enum('payment_type', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'crypto']);
            $table->enum('provider', [
                'visa', 'mastercard', 'amex', 'bcp', 'interbank',
                'scotiabank', 'bbva', 'yape', 'plin', 'paypal'
            ])->nullable();

            // Datos tarjeta
            $table->char('card_last_four', 4)->nullable();
            $table->string('card_brand', 20)->nullable();
            $table->string('card_holder_name')->nullable();
            $table->tinyInteger('card_expiry_month')->unsigned()->nullable();
            $table->smallInteger('card_expiry_year')->unsigned()->nullable();

            // Cuenta bancaria
            $table->string('bank_name', 100)->nullable();
            $table->string('account_number_masked', 50)->nullable();

            // Configuración y gateway
            $table->boolean('is_default')->default(false);
            $table->boolean('is_saved_for_future')->default(true);
            $table->string('gateway_token', 500)->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('metadata')->nullable()->comment('Datos adicionales específicos del proveedor');

            // Estado
            $table->enum('status', ['active', 'expired', 'blocked', 'pending_verification'])->default('active');
            $table->enum('verification_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status'], 'idx_payment_methods_user');
            $table->index(['user_id', 'is_default'], 'idx_payment_methods_default');
            $table->index(['payment_type', 'status'], 'idx_payment_methods_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};
