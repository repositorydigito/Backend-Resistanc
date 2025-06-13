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
        Schema::create('flavordrink_drink', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flavordrink_id')
                ->constrained('flavordrinks')
                ->onDelete('cascade')
                ->comment('ID del sabor de la bebida');
            $table->foreignId('drink_id')
                ->constrained('drinks')
                ->onDelete('cascade')
                ->comment('ID de la bebida');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flavordrink_drink');
    }
};
