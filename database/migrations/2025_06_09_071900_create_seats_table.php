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

            $table->integer('seat_number')->nullable()->comment('Asiento único');
            $table->integer('row')->comment('Fila del asiento');
            $table->integer('column')->comment('Columna del asiento');
            $table->boolean('is_active')->default(true)->comment('Estado del asiento');

            // Relaciones
            $table->foreignId('studio_id')->constrained('studios')->onDelete('cascade');

            // Índices y restricciones
            $table->index('is_active');
            $table->unique(['studio_id', 'row', 'column'], 'unique_seat_position');

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
