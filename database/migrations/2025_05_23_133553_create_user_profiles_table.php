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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 60)->comment('Nombre de la persona');
            $table->string('last_name', 60)->comment('Apellido de la persona');
            $table->date('birth_date')->nullable()->comment('Fecha de nacimiento');
            $table->enum('gender', ['female', 'male', 'other', 'na'])->default('na')->comment('Género');

            // Modificar campo existente
            $table->unsignedTinyInteger('shoe_size_eu')->nullable()->comment('Talla de calzado');

            // Agregar nuevos campos
            $table->string('profile_image')->nullable()->comment('Imagen de perfil');
            $table->text('bio')->nullable()->comment('Biografía');
            $table->string('emergency_contact_name', 100)->nullable()->comment('Nombre del contacto de emergencia');
            $table->string('emergency_contact_phone', 15)->nullable()->comment('Teléfono del contacto de emergencia');
            $table->text('medical_conditions')->nullable()->comment('Condiciones médicas');
            $table->text('fitness_goals')->nullable()->comment('Objetivos de fitness');

            // RELACIONES
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->unique('user_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
