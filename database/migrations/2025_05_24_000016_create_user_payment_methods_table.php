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

            $table->enum('payment_type', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'crypto'])->comment('Tipo de método de pago');
            $table->enum('provider', [
                'visa', 'mastercard', 'amex', 'bcp', 'interbank',
                'scotiabank', 'bbva', 'yape', 'plin', 'paypal'
            ])->nullable()->comment('Proveedor del método de pago');

            // Datos tarjeta
            $table->char('card_last_four', 4)->nullable()->comment('Últimos 4 dígitos de la tarjeta');
            $table->string('card_brand', 20)->nullable()->comment('Marca de la tarjeta');
            $table->string('card_holder_name')->nullable()->comment('Nombre del titular de la tarjeta');
            $table->tinyInteger('card_expiry_month')->unsigned()->nullable()->comment('Mes de expiración de la tarjeta');
            $table->smallInteger('card_expiry_year')->unsigned()->nullable()->comment('Año de expiración de la tarjeta');

            // Cuenta bancaria
            $table->string('bank_name', 100)->nullable()->comment('Nombre del banco');
            $table->string('account_number_masked', 50)->nullable()->comment('Número de cuenta enmascarado');

            // Configuración y gateway
            $table->boolean('is_default')->default(false)->comment('Indica si es el método de pago por defecto');
            $table->boolean('is_saved_for_future')->default(true)->comment('Indica si se guarda para futuros pagos');
            $table->string('gateway_token', 500)->nullable()->comment('Token del gateway de pago');
            $table->string('gateway_customer_id')->nullable()->comment('ID del cliente en el gateway de pago');
            $table->json('billing_address')->nullable()->comment('Dirección de facturación');
            $table->json('metadata')->nullable()->comment('Datos adicionales específicos del proveedor');

            // Estado
            $table->enum('status', ['active', 'expired', 'blocked', 'pending_verification', 'inactive'])->default('active')->comment('Estado del método de pago');
            $table->enum('verification_status', ['pending', 'verified', 'failed'])->default('pending')->comment('Estado de verificación del método de pago');
            $table->timestamp('last_used_at')->nullable()->comment('Última vez que se utilizó el método de pago');

            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->index(['user_id', 'status'], 'idx_payment_methods_user');
            $table->index(['user_id', 'is_default'], 'idx_payment_methods_default');
            $table->index(['payment_type', 'status'], 'idx_payment_methods_type');

            $table->timestamps();
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
