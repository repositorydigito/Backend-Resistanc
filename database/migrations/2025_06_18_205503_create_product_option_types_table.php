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
        Schema::create('product_option_types', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('Nombre del tipo de opción del producto');
            $table->string('slug')->unique()->comment('Slug único para el tipo de opción del producto');
            $table->boolean('is_color')->default(false)->comment('Indica si es un tipo de opción de color');
            $table->boolean('is_active')->default(true)->comment('Indica si el tipo de opción está activo');
            $table->boolean('is_required')->default(false)->comment('Indica si el tipo de opción es requerido');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_types');
    }
};
