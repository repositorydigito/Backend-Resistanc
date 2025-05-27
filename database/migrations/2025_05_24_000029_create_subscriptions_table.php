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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages')->onDelete('restrict');
            $table->foreignId('payment_method_id')->constrained('user_payment_methods')->onDelete('restrict');
            $table->string('subscription_code', 20)->unique();
            $table->string('name')->comment('Nombre descriptivo de la suscripción');
            $table->enum('status', ['active', 'paused', 'cancelled', 'failed', 'expired'])->default('active');
            $table->decimal('billing_amount', 8, 2);
            $table->char('currency', 3)->default('PEN');
            $table->enum('billing_frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->unsignedTinyInteger('trial_period_days')->default(0);
            $table->timestamp('trial_ends_at')->nullable();
            $table->date('next_billing_date');
            $table->date('last_billing_date')->nullable();
            $table->unsignedInteger('billing_cycle_count')->default(0);
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->unsignedTinyInteger('max_failed_attempts')->default(3);
            $table->unsignedTinyInteger('grace_period_days')->default(7);
            $table->boolean('auto_renew')->default(true);
            $table->boolean('prorate_charges')->default(true);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->enum('cancellation_requested_by', ['user', 'admin', 'system'])->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['next_billing_date', 'status']);
            $table->index('status');
            $table->index('package_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
