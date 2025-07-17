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
        Schema::create('drink_user', function (Blueprint $table) {
            $table->id();

            $table->enum('status', ['pending', 'completed', 'cancelled'])
                ->default('pending')->comment('Estado de la bebida para el usuario');

            $table->integer('quantity')->default(1)->comment('Cantidad de bebidas pedidas por el usuario');

            // Relaciones
            $table->foreignId('drink_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('classschedule_id')->nullable()
                ->constrained('class_schedules')
                ->onDelete('cascade');

            // Índices para performance
            $table->index(['user_id', 'drink_id']);
            $table->index('classschedule_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drink_user');
    }
};
