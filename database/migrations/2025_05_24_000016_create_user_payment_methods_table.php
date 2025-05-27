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
        Schema::create('user_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('payment_type', ['credit_card', 'debit_card', 'bank_transfer', 'digital_wallet', 'cash']);
            $table->string('provider', 50)->comment('visa, mastercard, yape, plin, etc.');
            $table->string('account_identifier')->comment('Últimos 4 dígitos, email, etc.');
            $table->string('account_holder_name');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Datos adicionales específicos del proveedor');
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'is_default']);
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payment_methods');
    }
};
