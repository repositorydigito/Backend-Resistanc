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
        Schema::table('users', function (Blueprint $table) {
            // Campos esenciales para facturación con Greenter
            $table->string('document_type', 10)->nullable()->after('email')->comment('Tipo de documento: DNI, RUC, CE, etc.');
            $table->string('document_number', 20)->nullable()->after('document_type')->comment('Número de documento de identidad');
            $table->string('business_name')->nullable()->after('document_number')->comment('Razón social o nombre comercial');
            $table->boolean('is_company')->default(false)->after('business_name')->comment('Indica si es una empresa');

            // Índices para optimizar consultas de facturación
            $table->index(['document_type', 'document_number'], 'idx_users_document');
            $table->index('is_company', 'idx_users_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_document');
            $table->dropIndex('idx_users_company');
            $table->dropColumn(['document_type', 'document_number', 'business_name', 'is_company']);
        });
    }
};
