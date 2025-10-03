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
        Schema::create('promocodes_package', function (Blueprint $table) {
            $table->id();

            $table->foreignId('package_id')->constrained('packages')->comment('Paquete relacionado');
            $table->foreignId('promo_codes_id')->constrained('promo_codes')->comment('Promocodes relacionado');

            $table->integer('quantity')->comment('cantidad de codigos generados');
            $table->string('discount')->comment('monto o porcentaje de descuento');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes_package');
    }
};
