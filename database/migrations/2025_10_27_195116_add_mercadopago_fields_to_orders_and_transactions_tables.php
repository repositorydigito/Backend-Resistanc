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
        // Agregar campos a la tabla transactions para Mercado Pago
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('mercadopago_preference_id')->nullable()->after('gateway_transaction_id')->comment('Preference ID de Mercado Pago');
            $table->string('mercadopago_collector_id')->nullable()->after('mercadopago_preference_id')->comment('Collector ID del vendedor en Mercado Pago');
            $table->string('mercadopago_application_fee', 50)->nullable()->after('mercadopago_collector_id')->comment('Fee de Mercado Pago');
        });

        // Agregar campos a la tabla orders para Mercado Pago
        Schema::table('orders', function (Blueprint $table) {
            $table->string('mercadopago_preference_id')->nullable()->after('payment_method_name')->comment('Preference ID de Mercado Pago');
            $table->string('mercadopago_payment_id')->nullable()->after('mercadopago_preference_id')->comment('Payment ID de Mercado Pago');
            $table->string('mercadopago_init_point_url', 500)->nullable()->after('mercadopago_payment_id')->comment('URL de pago de Mercado Pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir campos en transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['mercadopago_preference_id', 'mercadopago_collector_id', 'mercadopago_application_fee']);
        });

        // Revertir campos en orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['mercadopago_preference_id', 'mercadopago_payment_id', 'mercadopago_init_point_url']);
        });
    }
};
