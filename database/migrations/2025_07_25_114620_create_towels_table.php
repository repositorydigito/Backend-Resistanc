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
        Schema::create('towels', function (Blueprint $table) {
            $table->id();

            $table->string('code')->comment('Nombre de la toalla');
            $table->integer('size')->unsigned()->nullable()->comment('Tamaño de la toalla');
            $table->string('color')->nullable()->comment('Color de la toalla');
            $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex')->comment('Género de la toalla');

            $table->string('description')->nullable()->comment('Descripción de la toalla');
            $table->text('observations')->nullable()->comment('Observaciones sobre la toalla');
            $table->enum('status', ['available', 'maintenance', 'in_use', 'lost'])->default('available')->comment('Estado de la toalla');
            $table->string('image')->nullable()->comment('Imagen de la toalla');
            $table->json('images_gallery')->nullable()->comment('Galería de imágenes de la toalla');

            // Relaciones
            $table->foreignId('user_id')->nullable()->constrained()->comment('Usuario que creó la toalla');
            $table->foreignId('user_updated_id')->nullable()->constrained('users')->comment('Usuario que actualizó la toalla');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('towels');
    }
};
