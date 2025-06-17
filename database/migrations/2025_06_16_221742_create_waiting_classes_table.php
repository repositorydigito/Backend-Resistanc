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
        Schema::create('waiting_classes', function (Blueprint $table) {
            $table->id();

            // 🔗 Claves foránea
            $table->foreignId('class_schedules_id')
                ->constrained('class_schedules')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('user_package_id')
                ->nullable()
                ->constrained('user_packages')
                ->onDelete('set null');

            $table->enum('status', [
                'waiting',    // En espera
                'notified',   // Notificado
                'confirmed',  // Confirmado
                'expired',    // Expirado
                'cancelled'   // Cancelado
            ])->default('waiting');



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_classes');
    }
};
