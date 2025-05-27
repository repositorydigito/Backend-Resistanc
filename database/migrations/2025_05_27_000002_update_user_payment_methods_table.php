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
        Schema::table('user_payment_methods', function (Blueprint $table) {
            // Eliminar columnas que no coinciden con la estructura final
            $table->dropColumn(['account_identifier', 'account_holder_name', 'expiry_date', 'is_active']);
            
            // Actualizar enum de payment_type
            $table->enum('payment_type', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'crypto'])
                  ->change();
            
            // Actualizar enum de provider
            $table->enum('provider', ['visa', 'mastercard', 'amex', 'bcp', 'interbank', 'scotiabank', 'bbva', 'yape', 'plin', 'paypal'])
                  ->nullable()
                  ->change();
        });

        Schema::table('user_payment_methods', function (Blueprint $table) {
            // Agregar nuevas columnas específicas para tarjetas
            $table->char('card_last_four', 4)->nullable()->after('provider');
            $table->string('card_brand', 20)->nullable()->after('card_last_four');
            $table->string('card_holder_name')->nullable()->after('card_brand');
            $table->tinyInteger('card_expiry_month')->unsigned()->nullable()->after('card_holder_name');
            $table->smallInteger('card_expiry_year')->unsigned()->nullable()->after('card_expiry_month');
            
            // Campos para cuentas bancarias
            $table->string('bank_name', 100)->nullable()->after('card_expiry_year');
            $table->string('account_number_masked', 50)->nullable()->after('bank_name');
            
            // Configuración y estado
            $table->boolean('is_saved_for_future')->default(true)->after('is_default');
            
            // Tokens y referencias del gateway
            $table->string('gateway_token', 500)->nullable()->after('is_saved_for_future')
                  ->comment('Token del gateway de pago');
            $table->string('gateway_customer_id')->nullable()->after('gateway_token');
            
            // Dirección de facturación
            $table->json('billing_address')->nullable()->after('gateway_customer_id');
            
            // Estados
            $table->enum('status', ['active', 'expired', 'blocked', 'pending_verification'])
                  ->default('active')->after('billing_address');
            $table->enum('verification_status', ['pending', 'verified', 'failed'])
                  ->default('pending')->after('status');
            
            // Timestamp de último uso
            $table->timestamp('last_used_at')->nullable()->after('verification_status');
        });

        Schema::table('user_payment_methods', function (Blueprint $table) {
            // Actualizar índices
            $table->dropIndex(['user_id', 'is_active']);
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
        Schema::table('user_payment_methods', function (Blueprint $table) {
            // Eliminar índices nuevos
            $table->dropIndex('idx_payment_methods_user');
            $table->dropIndex('idx_payment_methods_default');
            $table->dropIndex('idx_payment_methods_type');
            
            // Eliminar columnas agregadas
            $table->dropColumn([
                'card_last_four', 'card_brand', 'card_holder_name', 
                'card_expiry_month', 'card_expiry_year', 'bank_name', 
                'account_number_masked', 'is_saved_for_future', 
                'gateway_token', 'gateway_customer_id', 'billing_address',
                'status', 'verification_status', 'last_used_at'
            ]);
        });

        Schema::table('user_payment_methods', function (Blueprint $table) {
            // Restaurar columnas originales
            $table->string('account_identifier')->comment('Últimos 4 dígitos, email, etc.');
            $table->string('account_holder_name');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Restaurar enums originales
            $table->enum('payment_type', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'cash'])
                  ->change();
            $table->string('provider', 50)->comment('visa, mastercard, yape, plin, etc.')->change();
            
            // Restaurar índices originales
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_default']);
            $table->index('payment_type');
        });
    }
};
