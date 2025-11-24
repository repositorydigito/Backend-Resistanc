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


            $table->decimal('stripe_commission_percentage', 5, 2)->default(3.60)->comment('Porcentaje de comisión que Stripe consume de cada venta (ej: 3.60 = 3.60%)');

            // Redes sociales individuales
            $table->string('facebook_url')->nullable()->comment('URL de Facebook');
            $table->string('instagram_url')->nullable()->comment('URL de Instagram');
            $table->string('twitter_url')->nullable()->comment('URL de Twitter');
            $table->string('linkedin_url')->nullable()->comment('URL de LinkedIn');
            $table->string('youtube_url')->nullable()->comment('URL de YouTube');
            $table->string('tiktok_url')->nullable()->comment('URL de TikTok');
            $table->string('whatsapp_url')->nullable()->comment('URL de WhatsApp');
            $table->string('website_url')->nullable()->comment('URL del sitio web');


            // Información fiscal de la empresa
            $table->string('ruc', 11)->nullable()->comment('RUC de la empresa');
            $table->string('commercial_name')->nullable()->comment('Nombre comercial de la empresa');

            // Configuración de facturación
            $table->string('invoice_series', 4)->default('F001')->comment('Serie de facturación (ej: F001)');
            $table->integer('invoice_initial_correlative')->default(1)->comment('Correlativo inicial de facturación');

            // Dirección desglosada para facturación
            $table->string('ubigeo', 6)->nullable()->comment('Ubigeo (código de ubicación geográfica)');
            $table->string('department')->nullable()->comment('Departamento');
            $table->string('province')->nullable()->comment('Provincia');
            $table->string('district')->nullable()->comment('Distrito');
            $table->string('urbanization')->nullable()->default('-')->comment('Urbanización');
            $table->string('establishment_code', 4)->default('0000')->comment('Código de establecimiento asignado por SUNAT');

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
