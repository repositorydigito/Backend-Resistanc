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
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();

            $table->date('scheduled_date')->comment('Fecha programada de la clase');
            $table->time('start_time')->comment('Hora de inicio de la clase');
            $table->time('end_time')->comment('Hora de fin de la clase');
            $table->unsignedTinyInteger('max_capacity')->nullable()->comment('Capacidad máxima de la clase');
            $table->unsignedTinyInteger('available_spots')->comment('Espacios disponibles');
            $table->unsignedTinyInteger('booked_spots')->default(0)->comment('Espacios reservados');
            $table->unsignedTinyInteger('waitlist_spots')->default(0)->comment('Espacios en lista de espera');
            $table->timestamp('booking_opens_at')->nullable()->comment('Cuándo se abre la reserva');
            $table->timestamp('booking_closes_at')->nullable()->comment('Cuándo se cierra la reserva');
            $table->timestamp('cancellation_deadline')->nullable()->comment('Fecha límite de cancelación');
            $table->text('special_notes')->nullable()->comment('Notas especiales');
            $table->boolean('is_holiday_schedule')->default(false)->comment('Indica si es un horario especial por feriado');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled')->comment('Estado del horario de la clase');
            $table->string('theme')->nullable();
            $table->string('img_url')->nullable();

            $table->boolean('is_replaced')->default(false);
            $table->foreignId('studio_id')->constrained('studios')->onDelete('restrict');

            // RELACIONES
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('restrict');
            $table->foreignId('substitute_instructor_id')
                ->nullable()
                ->constrained('instructors')
                ->onDelete('set null');

            // Índices
            $table->unique(['class_id', 'scheduled_date', 'start_time']);
            $table->index(['scheduled_date', 'status']);
            $table->index(['class_id', 'scheduled_date']);
            $table->index(['instructor_id', 'scheduled_date']);
            $table->index(['available_spots', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
