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
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('Nombre unico de la disciplina');
            $table->string('display_name', 100)->comment('Nombre que sera visual para el usuario');
            $table->text('description')->nullable()->comment('Descripcion de la disciplina');
            $table->string('icon_url')->nullable()->comment('icono de la disciplina');
            $table->string('color_hex', 7)->nullable()->comment('Color para UI (#FF5733)');
            $table->json('equipment_required')->nullable()->comment('Equipos necesarios');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('all_levels')->comment('Nivel de la disciplina');
            $table->unsignedInteger('calories_per_hour_avg')->nullable()->comment('calorias promedio quemadas por hora');
            $table->boolean('is_active')->default(true)->comment('Esta activo');
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('Orden de la disciplina');
            $table->timestamps();

            // Índices
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disciplines');
    }
};
