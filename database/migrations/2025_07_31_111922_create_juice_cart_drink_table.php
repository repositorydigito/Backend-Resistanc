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
        Schema::create('juice_cart_drink', function (Blueprint $table) {
            $table->id();

            $table->foreignId('juice_cart_code_id')
                ->constrained('juice_cart_codes')
                ->onDelete('cascade');
            $table->foreignId('drink_id')
                ->constrained('drinks')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juice_cart_drink');
    }
};
