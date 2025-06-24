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

            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('max_capacity');
            $table->unsignedTinyInteger('available_spots');
            $table->unsignedTinyInteger('booked_spots')->default(0);
            $table->unsignedTinyInteger('waitlist_spots')->default(0);
            $table->timestamp('booking_opens_at')->nullable()->comment('Cuándo se abre la reserva');
            $table->timestamp('booking_closes_at')->nullable()->comment('Cuándo se cierra la reserva');
            $table->timestamp('cancellation_deadline')->nullable();
            $table->text('special_notes')->nullable();
            $table->boolean('is_holiday_schedule')->default(false);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->timestamps();


            // Nuevo
            $table->string('img_url')->nullable();

            // RELACIONES
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('instructor_id')->constrained('instructors')->onDelete('restrict');
            // 👇 Nuevo campo para suplente
            $table->foreignId('substitute_instructor_id')
                ->nullable()
                ->constrained('instructors')
                ->onDelete('set null');

            $table->boolean('is_replaced')->default(false);
            $table->foreignId('studio_id')->constrained('studios')->onDelete('restrict');

            // Índices
            $table->unique(['class_id', 'scheduled_date', 'start_time']);
            $table->index(['scheduled_date', 'status']);
            $table->index(['class_id', 'scheduled_date']);
            $table->index(['instructor_id', 'scheduled_date']);
            $table->index(['available_spots', 'status']);
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
