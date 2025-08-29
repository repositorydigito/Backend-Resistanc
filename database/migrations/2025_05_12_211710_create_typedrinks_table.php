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
        Schema::create('typedrinks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nombre del tipo de bebida');
            $table->string('image_url')->nullable()->comment('Imagen del tipo de bebida');
            $table->string('ico_url')->nullable()->comment('Icono del tipo de bebida');
            $table->float('price')->default(0)->comment('Precio de la bebida');

            $table->boolean('is_active')->default(true)->comment('Indica si la bebida base está activa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('typedrinks');
    }
};
