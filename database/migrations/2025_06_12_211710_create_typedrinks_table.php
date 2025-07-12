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
            $table->string('name')->unique();
            // $table->string('slug')->unique();
            // $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('ico_url')->nullable();
            $table->float('price')->default(0)->comment('Precio de la bebida');
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
