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
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->comment('Nombre del servicio adicional');
            $table->text('description')->nullable()->comment('Descripción del servicio adicional');
            $table->decimal('price_soles', 10, 2)->comment('Precio del servicio adicional en soles');
            $table->unsignedInteger('duration_min')->comment('Duración del servicio adicional en minutos');
            $table->boolean('is_active')->default(true)->comment('Si el servicio adicional está activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_services');
    }
};
