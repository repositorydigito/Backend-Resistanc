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
        Schema::create('coach_ratings', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('score')->comment('Puntuación del coach, de 1 a 5');
            $table->text('comment')->nullable()->comment('Comentario del usuario sobre el coach');

            // Relaciones
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->unique(['instructor_id', 'user_id']);
            $table->index('user_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coach_ratings');
    }
};
