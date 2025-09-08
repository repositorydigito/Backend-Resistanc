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
        Schema::create('template_emails', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('nombre de la plantilla'); // Nombre de la plantilla (ej: "Bienvenida", "Recordatorio")
            $table->string('subject')->comment('asunto del correo'); // Asunto del correo
            $table->string('title')->nullable()->comment('título del correo'); // Título del correo (diferente del asunto)
            $table->text('body')->comment('cuerpo del correo'); // Cuerpo del correo (puede ser HTML o texto plano)
            $table->json('attachments')->nullable()->comment('Json con rutas/urls de imagenes o archivos'); // JSON con rutas/URLs de imágenes o archivos adjuntos
            $table->json('metadata')->nullable(); // JSON para metadatos adicionales (ej: variables dinámicas)
            $table->boolean('is_active')->default(true); // Para activar/desactivar la plantilla

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_emails');
    }
};
