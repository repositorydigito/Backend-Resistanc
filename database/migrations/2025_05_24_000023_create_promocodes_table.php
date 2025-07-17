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

        // no utilizado
        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_shipping', 'buy_x_get_y']);
            $table->decimal('discount_value', 8, 2);
            $table->decimal('minimum_amount', 8, 2)->nullable()->comment('Monto mínimo para aplicar');
            $table->decimal('maximum_discount', 8, 2)->nullable()->comment('Descuento máximo aplicable');
            $table->enum('applicable_to', ['packages', 'products', 'both'])->default('both');
            $table->json('applicable_items')->nullable()->comment('IDs específicos si aplica');
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedTinyInteger('usage_limit_per_user')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->datetime('starts_at')->nullable(); // o usar una fecha específica
            $table->datetime('expires_at')->nullable(); // o usar una fecha específica;
            $table->boolean('is_active')->default(true);
            $table->boolean('is_first_time_only')->default(false);
            $table->json('target_audience')->nullable()->comment('Criterios de audiencia objetivo');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices
            $table->index(['code', 'is_active']);
            $table->index(['is_active', 'starts_at', 'expires_at']);
            $table->index(['applicable_to', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes');
    }
};
