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
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();


            $table->string('favoritable_type')->comment('Tipo de entidad que se está guardando como favorito');
            $table->string('favoritable_id')->comment('ID de la entidad que se está guardando como favorito');

            $table->text('notes')->nullable()->comment('Notas adicionales sobre el favorito');
            $table->integer('priority')->default(0)->comment('Prioridad del favorito, 0 es normal, 1 es alto, -1 es bajo');

            // Relaciones
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Indices
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'user_favorites_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
