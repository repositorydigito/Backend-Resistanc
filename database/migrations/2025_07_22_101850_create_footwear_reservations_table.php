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
        Schema::create('footwear_reservations', function (Blueprint $table) {
            $table->id();

            // Fechas clave
            $table->dateTime('reservation_date')->useCurrent()->comment('Fecha de creación de la reserva');
            $table->dateTime('scheduled_date')->comment('Fecha programada para el préstamo');
            $table->dateTime('expiration_date')->nullable()->comment('Fecha límite para concretar el préstamo');

            // Estado y detalles
            $table->enum('status', ['pending', 'confirmed', 'canceled', 'expired'])->default('pending');

            // Relaciones
            $table->foreignId('class_schedules_id')->constrained('class_schedules')->onDelete('cascade')->comment('Clase asociada a la reserva');
            $table->foreignId('footwear_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_client_id')->constrained('users')->comment('Cliente que reserva');
            $table->foreignId('user_id')->nullable()->constrained()->comment('Usuario que registra la reserva');

            // $table->foreignId('loan_id')->nullable()->constrained('footwear_loans')->comment('Préstamo generado'); // Eliminado para evitar referencia circular

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footwear_reservations');
    }
};
