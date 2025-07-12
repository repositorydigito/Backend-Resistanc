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
            $table->string('email')->index();
            $table->string('code', 4);
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['email', 'code']);
            $table->index(['email', 'used']);
            $table->index('expires_at');
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
