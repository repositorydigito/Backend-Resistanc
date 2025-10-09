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
            $table->foreignId('package_id')->nullable()->constrained('packages')->comment('Paquete al que se aplicó el código');

            $table->decimal('monto', 10, 2)->nullable()->comment('Monto pagado con el descuento');
            $table->decimal('discount_applied', 5, 2)->nullable()->comment('Porcentaje de descuento aplicado');
            $table->decimal('original_price', 10, 2)->nullable()->comment('Precio original del paquete');
            $table->decimal('final_price', 10, 2)->nullable()->comment('Precio final después del descuento');

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
