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
        // no utilizado
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->unsignedTinyInteger('seat_number');
            $table->char('seat_row', 1)->nullable()->comment('A, B, C, etc. para organización visual');
            $table->unsignedTinyInteger('seat_position')->nullable()->comment('1, 2, 3, etc. dentro de la fila');
            $table->enum('status', ['available', 'reserved', 'occupied', 'maintenance'])->default('available');
            $table->timestamp('reserved_until')->nullable()->comment('Tiempo límite de reserva temporal');
            $table->enum('equipment_type', ['bike', 'reformer', 'mat'])->nullable()->comment('Tipo de equipo en la posición');
            $table->string('equipment_id', 50)->nullable()->comment('ID específico del equipo');
            $table->text('special_needs')->nullable()->comment('Notas especiales del puesto');
            $table->timestamps();

            // Índices
            $table->unique(['class_schedule_id', 'seat_number']);
            $table->index(['class_schedule_id', 'status']);
            $table->index('status');
            $table->index(['class_schedule_id', 'status', 'seat_number']);
            $table->index('booking_id');

            // Note: Check constraints will be handled at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
