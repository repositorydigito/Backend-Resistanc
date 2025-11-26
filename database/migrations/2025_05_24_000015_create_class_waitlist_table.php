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
        Schema::create('class_waitlist', function (Blueprint $table) {
            $table->id();

            $table->timestamp('joined_at')->useCurrent()->comment('Fecha y hora en que el usuario se unió a la lista de espera');
            $table->enum('status', ['waiting', 'notified', 'accepted', 'expired'])->default('waiting')->comment('Estado del usuario en la lista de espera');
            $table->timestamp('created_at')->nullable()->comment('Fecha y hora de creación del registro');
            $table->timestamp('updated_at')->nullable()->comment('Fecha y hora de actualización del registro');

            // Relaciones
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->onDelete('cascade')->comment('ID del horario de la clase');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ID del usuario en la lista de espera');

            // Índices
            $table->unique(['class_schedule_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_waitlist');
    }
};
