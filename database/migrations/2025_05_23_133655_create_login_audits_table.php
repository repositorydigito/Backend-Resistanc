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
        Schema::create('login_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('email_attempted')->nullable();
            $table->string('ip', 45);
            $table->string('user_agent');
            $table->enum('login_method', ['email_password', 'social_google', 'social_facebook', 'social_apple'])->default('email_password');
            $table->boolean('success');
            $table->enum('failure_reason', ['invalid_credentials', 'account_suspended', 'email_not_verified', 'too_many_attempts', 'other'])->nullable();
            $table->string('session_id')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->string('location_country', 100)->nullable();
            $table->string('location_city', 100)->nullable();

            $table->timestamp('created_at')->useCurrent();



            // Índices
            $table->index('user_id');
            $table->index('created_at');
            $table->index(['ip', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index(['email_attempted', 'created_at']);
            $table->index(['ip', 'success', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_audits');
    }
};
