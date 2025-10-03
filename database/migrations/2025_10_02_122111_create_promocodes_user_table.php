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
        Schema::create('promocodes_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->comment('Usuario relacionado');
            $table->foreignId('promo_codes_id')->constrained('promo_codes')->comment('Promocodes relacionado');

            $table->string('monto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes_user');
    }
};
