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
        Schema::create('class_schedule_seat', function (Blueprint $table) {
            $table->id();
            // 🔗 Claves foráneas
            $table->foreignId('class_schedules_id')
                ->constrained('class_schedules')
                ->onDelete('cascade');

            $table->foreignId('seats_id')
                ->constrained('seats')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // 📊 Estados del asiento
            $table->enum('status', [
                'available',    // Disponible
                'reserved',     // Reservado
                'occupied',     // Ocupado
                'Completed',    // Completado
                'blocked'       // Bloqueado
            ])->default('available');




            // 🔒 Índices únicos y compuestos
            $table->unique(['class_schedules_id', 'seats_id'], 'unique_schedule_seat');
            $table->index(['class_schedules_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');

            // 📅 Timestamps
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Para reservas temporales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedule_seat');
    }
};
