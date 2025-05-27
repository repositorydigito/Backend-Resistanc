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
        Schema::create('membership_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('membership_level', ['resistance', 'gold', 'black']);
            $table->enum('benefit_type', ['priority_booking', 'discount_percentage', 'free_shakes', 'auto_enrollment', 'guest_passes', 'personal_training']);
            $table->json('benefit_value')->comment('Valor específico del beneficio');
            $table->unsignedInteger('monthly_allowance')->nullable()->comment('Cantidad mensual permitida');
            $table->unsignedInteger('monthly_used')->default(0);
            $table->unsignedInteger('yearly_allowance')->nullable();
            $table->unsignedInteger('yearly_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('activated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_reset_at')->nullable()->comment('Último reset mensual/anual');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Índices
            $table->index(['user_id', 'membership_level']);
            $table->index(['membership_level', 'benefit_type']);
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_benefits');
    }
};
