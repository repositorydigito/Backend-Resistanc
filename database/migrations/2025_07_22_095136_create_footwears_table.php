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
        Schema::create('footwears', function (Blueprint $table) {
            $table->id();

            $table->string('code')->comment('Nombre del calzado');
            $table->string('model')->nullable()->comment('Modelo del calzado');
            $table->string('brand')->nullable()->comment('Marca del calzado');
            $table->integer('size')->unsigned()->nullable()->comment('Tamaño del calzado');
            $table->string('color')->nullable()->comment('Color del calzado');
            $table->enum('type', ['sneakers', 'boots', 'sandals', 'formal'])->default('sneakers')->comment('Tipo de calzado');
            $table->enum('gender', ['male', 'female', 'unisex'])->default('unisex')->comment('Género del calzado');

            $table->string('description')->nullable()->comment('Descripción del calzado');
            $table->text('observations')->nullable()->comment('Observaciones sobre el calzado');
            $table->enum('status', ['available', 'out_of_stock', 'maintenance', 'in_use', 'lost'])->default('available')->comment('Estado del calzado');
            $table->string('image')->nullable()->comment('Imagen del calzado');
            $table->json('images_gallery')->nullable()->comment('Galería de imágenes del calzado');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footwears');
    }
};
