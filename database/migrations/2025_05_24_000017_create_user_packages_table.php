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
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages')->onDelete('restrict');
            $table->string('package_code', 20)->unique();
            $table->unsignedInteger('total_classes');
            $table->unsignedInteger('used_classes')->default(0);
            $table->unsignedInteger('remaining_classes')->default(0);
            $table->decimal('amount_paid_soles', 8, 2);
            $table->char('currency', 3)->default('PEN');
            $table->date('purchase_date');
            $table->date('activation_date')->nullable();
            $table->date('expiry_date');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'suspended'])->default('pending');
            $table->boolean('auto_renew')->default(false);
            $table->decimal('renewal_price', 8, 2)->nullable();
            $table->json('benefits_included')->nullable()->comment('Beneficios específicos incluidos');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['package_id', 'status']);
            $table->index('expiry_date');
            $table->index('package_code');
            $table->index(['user_id', 'expiry_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_packages');
    }
};
