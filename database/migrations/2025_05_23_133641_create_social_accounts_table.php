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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // $table->enum('provider', ['google', 'facebook']);
            $table->string('provider_uid', 191);
            $table->string('provider_email', 191)->nullable();
            $table->text('token'); // Será encriptado en el modelo
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index('user_id');
            // $table->unique(['provider', 'provider_uid']);
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
