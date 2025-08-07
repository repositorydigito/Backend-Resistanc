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
            $table->string('first_name')->comment('Nombre de la persona');
            $table->string('last_name')->comment('Apellido de la persona');
            // $table->string('email', 100)->unique()->comment('Correo electrónico');

            $table->date('birth_date')->nullable()->comment('Fecha de nacimiento');
            $table->enum('gender', ['female', 'male', 'other', 'na'])->default('na')->comment('Género');

            $table->unsignedTinyInteger('shoe_size_eu')->nullable()->comment('Talla de calzado');

            // Agregar nuevos campos
            $table->string('profile_image')->nullable()->comment('Imagen de perfil');
            $table->text('bio')->nullable()->comment('Biografía');
            $table->string('emergency_contact_name', 100)->nullable()->comment('Nombre del contacto de emergencia');
            $table->string('emergency_contact_phone', 15)->nullable()->comment('Teléfono del contacto de emergencia');
            $table->text('medical_conditions')->nullable()->comment('Condiciones médicas');
            $table->text('fitness_goals')->nullable()->comment('Objetivos de fitness');

            $table->boolean('is_active')->default(true)->comment('Indica si el perfil está activo');
            $table->string('observations')->nullable()->comment('Observaciones adicionales');


            // RELACIONES
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->unique('user_id');

            $table->timestamps();
            $table->softDeletes();
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
