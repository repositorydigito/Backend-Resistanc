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
        Schema::create('benefit_usage_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('benefit_id')->constrained('membership_benefits')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->enum('usage_type', ['priority_booking', 'discount_applied', 'free_shake', 'auto_enrollment', 'guest_pass', 'pt_session']);
            $table->decimal('usage_value', 8, 2)->nullable()->comment('Valor monetario del beneficio usado');
            $table->json('usage_details')->nullable()->comment('Detalles específicos del uso');
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->timestamp('used_at')->useCurrent();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Índices
            $table->index(['user_id', 'period_year', 'period_month']);
            $table->index(['benefit_id', 'used_at']);
            $table->index(['period_year', 'period_month']);
            $table->index('booking_id');
            $table->index('order_id');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_usage_log');
    }
};
