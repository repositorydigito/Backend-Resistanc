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
        Schema::create('drinks', function (Blueprint $table) {
            $table->id();

            // $table->string('name')->comment('Nombre de la bebida');
            // $table->string('slug')->unique()->comment('Slug único para la bebida');
            // $table->string('description')->nullable()->comment('Descripción de la bebida');
            // $table->string('image_url')->nullable()->comment('URL de la imagen de la bebida');

            // Relaciones
            $table->foreignId('typedrink_id')
                ->nullable()
                ->constrained('typedrinks')
                ->onDelete('cascade')
                ->comment('ID del tipo de bebida');

            $table->foreignId('basedrink_id')
                ->nullable()
                ->constrained('basedrinks')
                ->onDelete('cascade')
                ->comment('ID de la base de bebida');

            $table->foreignId('flavordrink_id')
                ->nullable()
                ->constrained('flavordrinks')
                ->onDelete('cascade')
                ->comment('ID del sabor de la bebida');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drinks');
    }
};
