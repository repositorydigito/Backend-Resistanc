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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            // Cambiar row y column a integer
            $table->integer('seat_number')->nullable(); // Asiento único
            $table->integer('row');
            $table->integer('column');

            // Agregar campo is_active
            $table->boolean('is_active')->default(true);

            $table->foreignId('studio_id')->constrained('studios')->onDelete('cascade');

            // Agregar índice único para evitar duplicados
            $table->unique(['studio_id', 'row', 'column'], 'unique_seat_position');

            // Agregar índice para is_active
            $table->index('is_active');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
