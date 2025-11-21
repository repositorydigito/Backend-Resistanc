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
            // Información fiscal de la empresa
            $table->string('ruc', 11)->nullable()->after('social_reason')->comment('RUC de la empresa');
            $table->string('commercial_name')->nullable()->after('ruc')->comment('Nombre comercial de la empresa');
            
            // Configuración de facturación
            $table->string('invoice_series', 4)->default('F001')->after('is_production')->comment('Serie de facturación (ej: F001)');
            $table->integer('invoice_initial_correlative')->default(1)->after('invoice_series')->comment('Correlativo inicial de facturación');
            
            // Dirección desglosada para facturación
            $table->string('ubigeo', 6)->nullable()->after('address')->comment('Ubigeo (código de ubicación geográfica)');
            $table->string('department')->nullable()->after('ubigeo')->comment('Departamento');
            $table->string('province')->nullable()->after('department')->comment('Provincia');
            $table->string('district')->nullable()->after('province')->comment('Distrito');
            $table->string('urbanization')->nullable()->default('-')->after('district')->comment('Urbanización');
            $table->string('establishment_code', 4)->default('0000')->after('urbanization')->comment('Código de establecimiento asignado por SUNAT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'ruc',
                'commercial_name',
                'invoice_series',
                'invoice_initial_correlative',
                'ubigeo',
                'department',
                'province',
                'district',
                'urbanization',
                'establishment_code',
            ]);
        });
    }
};
