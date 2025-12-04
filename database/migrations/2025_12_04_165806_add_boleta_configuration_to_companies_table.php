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
        Schema::table('companies', function (Blueprint $table) {
            // Configuración de boletas (similar a facturas)
            $table->string('boleta_series', 4)->default('B001')->after('invoice_initial_correlative')->comment('Serie de boletas (ej: B001)');
            $table->integer('boleta_initial_correlative')->default(1)->after('boleta_series')->comment('Correlativo inicial de boletas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['boleta_series', 'boleta_initial_correlative']);
        });
    }
};
