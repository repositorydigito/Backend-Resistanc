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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('Nombre de la clase');
            $table->enum('type', ['presencial', 'en_vivo', 'grabada'])->default('presencial')->comment('Tipo de clase');
            $table->unsignedTinyInteger('duration_minutes')->comment('Duración de la clase en minutos');
            $table->unsignedTinyInteger('max_capacity')->comment('Capacidad máxima de la clase');
            $table->text('description')->nullable()->comment('Descripción de la clase');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('all_levels')->comment('Nivel de dificultad');
            $table->string('music_genre', 100)->nullable()->comment('Género musical de la clase');
            $table->text('special_requirements')->nullable()->comment('Requisitos especiales de la clase');
            $table->boolean('is_featured')->default(false)->comment('Si la clase es destacada');
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active')->comment('Estado de la clase');
            $table->string('img_url')->nullable()->comment('URL de la imagen de la clase');

            // Relaciones
            $table->foreignId('discipline_id')->constrained('disciplines')->onDelete('restrict');

            // Índices
            $table->index(['discipline_id', 'status']);
            $table->index(['type', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
