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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Campos de dirección fiscal para facturación con Greenter
            $table->string('fiscal_address')->nullable()->after('adress')->comment('Dirección fiscal completa');
            $table->string('district')->nullable()->after('fiscal_address')->comment('Distrito');
            $table->string('province')->nullable()->after('district')->comment('Provincia');
            $table->string('department')->nullable()->after('province')->comment('Departamento');
            $table->string('ubigeo', 6)->nullable()->after('department')->comment('Código de ubicación geográfica');

            // Corrección del typo en 'adress' -> 'address'
            $table->renameColumn('adress', 'address');

            // Índices para optimizar consultas de facturación
            $table->index('ubigeo', 'idx_user_profiles_ubigeo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_user_profiles_ubigeo');
            $table->dropColumn(['fiscal_address', 'district', 'province', 'department', 'ubigeo']);
            $table->renameColumn('address', 'adress');
        });
    }
};
