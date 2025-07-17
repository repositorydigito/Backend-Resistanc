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
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();

            $table->string('email')->index()->comment('Correo electrónico del usuario que solicita el restablecimiento de contraseña');
            $table->string('code', 4)->comment('Código de restablecimiento de contraseña');
            $table->timestamp('expires_at')->comment('Fecha y hora de expiración del código');
            $table->boolean('used')->default(false)->comment('Indica si el código ha sido utilizado');
            $table->timestamp('used_at')->nullable()->comment('Fecha y hora en que se utilizó el código');

            // Índices para optimizar consultas
            $table->index(['email', 'code']);
            $table->index(['email', 'used']);
            $table->index('expires_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
