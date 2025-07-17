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
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nombre de la sala');
            $table->string('location')->comment('Ubicación de la sala');
            $table->unsignedTinyInteger('max_capacity')->comment('Capacidad máxima de la sala');
            $table->json('equipment_available')->nullable()->comment('Equipamiento disponible en la sala');
            $table->json('amenities')->nullable()->comment('Vestuarios, duchas, etc.')->comment('Amenidades disponibles en la sala');
            $table->enum('studio_type', ['cycling', 'reformer', 'mat', 'multipurpose'])->comment('Tipo de sala');
            $table->boolean('is_active')->default(true)->comment('Indica si la sala está activa');
            $table->unsignedTinyInteger('capacity_per_seat')->nullable()->comment('Capacidad por asiento');

            // Informacion de las butacas
            $table->enum('addressing', ['right_to_left', 'left_to_right', 'center'])->comment('Dirección de la sala');
            $table->integer('row')->nullable()->comment('Fila de la sala');
            $table->integer('column')->nullable()->comment('Columna de la sala');
            // Fin Informacion de las butacas

            // Índices
            $table->index(['studio_type', 'is_active']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studios');
    }
};
