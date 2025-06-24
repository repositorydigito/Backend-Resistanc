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
        Schema::create('variant_option', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // Ej: azul, rojo, S, M, L
            $table->foreignId('product_option_type_id')->constrained('product_option_types')->onDelete('cascade');

            $table->string('value'); // Ej: S, M, L, Negro

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_option');
    }
};
