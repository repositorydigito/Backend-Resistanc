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
        Schema::create('flavordrinks', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique()->comment('Nombre del sabor de la bebida');
            $table->string('image_url')->nullable()->comment('URL de la imagen del sabor de la bebida');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flavordrinks');
    }
};
