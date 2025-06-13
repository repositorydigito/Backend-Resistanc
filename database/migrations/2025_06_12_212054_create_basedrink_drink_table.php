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
        Schema::create('basedrink_drink', function (Blueprint $table) {
            $table->id();
            $table->foreignId('basedrink_id')
                ->constrained('basedrinks')
                ->onDelete('cascade')
                ->comment('ID de la bebida base');
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
        Schema::dropIfExists('basedrink_drink');
    }
};
