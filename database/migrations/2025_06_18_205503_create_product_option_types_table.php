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

            $table->string('name'); // Ej: Talla, Color
            $table->string('slug')->unique();
            $table->boolean('is_color')->default(false); // Indica si es un tipo de opción de color
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);

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
