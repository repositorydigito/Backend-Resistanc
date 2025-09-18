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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();

            $table->enum('provider', ['google', 'facebook', 'apple', 'instagram', 'tiktok'])->comment('Proveedor');
            $table->string('provider_uid', 191)->comment('ID del proveedor');
            $table->string('provider_email', 191)->nullable()->comment('Email de proveedor');
            $table->string('provider_name')->nullable()->comment('Nombre del proveedor');
            $table->string('provider_avatar', 500)->nullable()->comment('Avatar del proveedor');
            $table->text('token')->comment('Token de acceso'); // Será encriptado en el modelo
            $table->timestamp('token_expires_at')->nullable()->comment('Fecha de expiración del token');

            $table->boolean('is_active')->default(true)->comment('Esta activo?');

            $table->text('refresh_token')->nullable()->comment('Token actualizado');

            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Índices
            $table->index('user_id');
            $table->index(['provider', 'is_active']);
            $table->index('provider_email');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
