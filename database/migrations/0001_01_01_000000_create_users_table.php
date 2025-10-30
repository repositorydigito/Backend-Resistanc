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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Codigo unico de usuario');
            $table->string('name')->comment('Nombre del usuario');
            $table->string('email')->unique('Email del usuario');
            $table->timestamp('email_verified_at')->nullable()->comment('Verificacion del usuario');
            $table->string('password')->nullable()->comment('Contrasenia del usuario');
            $table->string('google_id')->nullable()->comment('id del logueo por google');
            $table->string('facebook_id')->nullable()->comment('id del logueo por facebook');
             $table->string('avatar')->nullable()->comment('URL del avatar del usuario');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
