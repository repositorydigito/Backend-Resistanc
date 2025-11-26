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
        Schema::create('drinks', function (Blueprint $table) {
            $table->id();

            // Campos de precio y datos históricos
            $table->decimal('base_price_soles', 10, 2)->default(0.00)->comment('Precio base de la bebida');
            $table->decimal('total_price_soles', 10, 2)->default(0.00)->comment('Precio total calculado');
            $table->string('drink_name', 255)->nullable()->comment('Nombre completo de la bebida para historial');
            $table->json('drink_combination')->nullable()->comment('Combinación de ingredientes para historial');
            $table->boolean('is_active')->default(true)->comment('Estado de disponibilidad');

            // Relaciones
            $table->foreignId('typedrink_id')
                ->nullable()
                ->constrained('typedrinks')
                ->onDelete('cascade')
                ->comment('ID del tipo de bebida');

            $table->foreignId('basedrink_id')
                ->nullable()
                ->constrained('basedrinks')
                ->onDelete('cascade')
                ->comment('ID de la base de bebida');

            $table->foreignId('flavordrink_id')
                ->nullable()
                ->constrained('flavordrinks')
                ->onDelete('cascade')
                ->comment('ID del sabor de la bebida');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drinks');
    }
};
