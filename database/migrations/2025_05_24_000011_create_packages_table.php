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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            // Datos principales
            $table->string('name', 100)->comment('Nombre del paquete');
            $table->string('slug', 100)->comment('Slug del paquete');
            $table->text('description')->comment('Descripción del paquete');
            $table->string('short_description')->nullable()->comment('Descripción corta del paquete');
            $table->unsignedInteger('classes_quantity')->comment('Cantidad de clases en el paquete');
            $table->decimal('price_soles', 8, 2)->comment('Precio del paquete en soles');
            $table->decimal('original_price_soles', 8, 2)->nullable()->comment('Precio original para mostrar descuentos');


            // Configuración de facturación
            $table->enum('billing_type', ['one_time', 'monthly', 'quarterly', 'yearly'])->default('one_time')->comment('Tipo de facturación del paquete');
            $table->boolean('is_virtual_access')->default(false)->comment('Si el paquete tiene acceso virtual');
            $table->unsignedTinyInteger('priority_booking_days')->default(0)->comment('Días de anticipación para reservar');
            $table->boolean('auto_renewal')->default(false)->comment('Si el paquete se renueva automáticamente');

            // Branding y visual
            $table->boolean('is_featured')->default(false)->comment('Si el paquete es destacado');
            $table->boolean('is_popular')->default(false)->comment('Si el paquete es popular');
            $table->unsignedTinyInteger('display_order')->default(0)->comment('Orden de visualización del paquete');
            $table->string('color_hex')->nullable()->default('#d4691a')->comment('Color del paquete');

            // Tipos y segmentación
            $table->enum('status', ['active', 'inactive', 'coming_soon', 'discontinued'])->default('active')->comment('Estado del paquete');
            $table->enum('buy_type', ['affordable', 'assignable'])->default('affordable')->comment('Tipo de compra del paquete');
            $table->enum('type', ['free_trial','fixed', 'temporary'])->comment('Tipo de paquete');
            $table->date('start_date')->nullable()->comment('Fecha de inicio del paquete');
            $table->date('end_date')->nullable()->comment('Fecha de finalización del paquete');
            $table->enum('mode_type', ['presencial', 'virtual', 'mixto'])->default('presencial')->comment('Tipo de modalidad del paquete');
            $table->enum('commercial_type', ['promotion', 'offer', 'basic'])->default('basic')->comment('Tipo comercial del paquete');
            $table->enum('target_audience', ['beginner', 'intermediate', 'advanced', 'all'])->default('all')->comment('Público objetivo del paquete');

            // Extras
            $table->json('features')->nullable()->comment('Características y beneficios del paquete')->comment('Características y beneficios del paquete');
            $table->json('restrictions')->nullable()->comment('Restricciones y condiciones')->comment('Restricciones y condiciones del paquete');

            // nuevo
            $table->string('icon_url')->nullable()->comment('URL de la imagen del paquete');
            $table->integer('duration_in_months')->nullable()->comment('Duración del paquete en meses');

            // Relaciones
            $table->foreignId('membership_id')->nullable()->constrained('memberships')->onDelete('set null');
            $table->foreignId('discipline_id')->constrained('disciplines');

            // Índices
            $table->index(['status', 'display_order']);
            $table->index(['mode_type', 'status']);
            $table->index(['is_featured', 'is_popular']);
            $table->index('price_soles');
            $table->index('membership_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
