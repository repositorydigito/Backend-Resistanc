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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('notification_type', [
                'booking_reminder', 'class_cancelled', 'package_expiring', 'payment_failed', 
                'membership_upgrade', 'promotion', 'system_maintenance'
            ]);
            $table->string('title');
            $table->text('message');
            $table->string('action_url', 500)->nullable();
            $table->string('action_text', 50)->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('channels')->comment('push, email, sms');
            $table->timestamp('scheduled_for')->nullable()->comment('Para notificaciones programadas');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'cancelled'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['scheduled_for', 'status']);
            $table->index(['notification_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
