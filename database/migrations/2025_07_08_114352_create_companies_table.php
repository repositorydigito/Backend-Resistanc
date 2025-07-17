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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique()->comment('Nombre de la empresa');
            $table->string('legal_name')->nullable()->comment('Nombre legal de la empresa');
            $table->string('tax_id', 20)->unique()->comment('Número de identificación fiscal');
            $table->string('address')->comment('Dirección de la empresa');
            $table->string('phone', 20)->comment('Teléfono de la empresa');
            $table->string('email')->nullable()->comment('Correo electrónico de la empresa');
            $table->string('logo_path')->nullable()->comment('Ruta del logotipo de la empresa');
            $table->string('website')->nullable()->comment('Sitio web de la empresa');
            $table->json('settings')->nullable()->comment('Configuraciones de la empresa');
            $table->string('timezone')->default('UTC')->comment('Zona horaria de la empresa');
            $table->string('currency', 3)->default('SOL')->comment('Moneda de la empresa');
            $table->string('locale', 5)->default('es_PE')->comment('Idioma de la empresa');

            // Facturacion con nube Fac
            $table->text('url_facturacion')->nullable()->comment('Proveedor de facturación');
            $table->text('token_facturacion')->nullable()->comment('Token de acceso para la API de facturación');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
