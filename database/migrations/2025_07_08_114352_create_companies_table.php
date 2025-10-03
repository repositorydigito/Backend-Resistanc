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
            $table->string('social_reason')->nullable()->comment('Razon social de la empresa');
            $table->string('address')->comment('Dirección de la empresa');
            $table->string('phone', 20)->comment('Teléfono de la empresa');
            $table->string('phone_whassap', 20)->comment('Teléfono de whassap');
            $table->string('phone_help', 20)->comment('Teléfono de ayuda');
            $table->string('email')->nullable()->comment('Correo electrónico de la empresa');
            $table->string('logo_path')->nullable()->comment('Ruta del logotipo de la empresa');


            $table->string('signature_image')->nullable()->comment('Ruta de la imagen de la firma');
            $table->json('social_networks')->nullable()->comment('Redes sociales');

            // Facturacion con Greenter
            $table->boolean('is_production')->default(false)->comment('Para que empiece a facturar');

            // Produccion
            $table->string('sol_user_production')->nullable()->comment('Usuario sol produccion');
            $table->string('sol_user_password_production')->nullable()->comment('Contraseña del Usuario sol produccion');
            $table->string('cert_path_production')->nullable()->comment('Ruta donde guardo mi certificado digital produccion');

            $table->string('client_id_production')->nullable()->comment('client id de produccion');
            $table->string('client_secret_production')->nullable()->comment('client secret de produccion');

            // Pruebas
            $table->string('sol_user_evidence')->nullable()->comment('Usuario sol prueba QA');
            $table->string('sol_user_password_evidence')->nullable()->comment('Contraseña del Usuario sol QA');
            $table->string('cert_path_evidence')->nullable()->comment('Ruta donde guardo mi certificado digital QA');

            $table->string('client_id_evidence')->nullable()->comment('client id de QA');
            $table->string('client_secret_evidence')->nullable()->comment('client secret de QA');

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
