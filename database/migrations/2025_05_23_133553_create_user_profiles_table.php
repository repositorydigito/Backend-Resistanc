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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['female', 'male', 'other', 'na'])->default('na');

            // Modificar campo existente
            $table->unsignedTinyInteger('shoe_size_eu')->nullable();

            // Agregar nuevos campos
            $table->string('profile_image')->nullable();
            $table->text('bio')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 15)->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('fitness_goals')->nullable();


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
