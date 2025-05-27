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
        Schema::create('booking_extra_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null')->comment('Producto base si aplica');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('set null')->comment('Variante específica si aplica');
            $table->enum('option_type', [
                'shake_base', 'shake_flavor', 'supplement_type', 'recovery_service',
                'wellness_preference', 'dietary_restriction', 'special_request',
                'equipment_preference', 'music_preference', 'intensity_level'
            ]);
            $table->string('option_value', 200);
            $table->text('option_description')->nullable()->comment('Descripción detallada de la personalización');
            $table->decimal('additional_cost', 8, 2)->default(0.00);
            $table->boolean('is_free_benefit')->default(false)->comment('Si es beneficio gratuito de membresía');
            $table->decimal('discount_applied', 8, 2)->default(0.00);
            $table->enum('status', ['pending', 'confirmed', 'prepared', 'delivered', 'cancelled'])->default('pending');
            $table->text('preparation_notes')->nullable()->comment('Notas para preparación en cocina/servicio');
            $table->foreignId('prepared_by_staff_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index('booking_id');
            $table->index('product_id');
            $table->index('product_variant_id');
            $table->index(['option_type', 'status']);
            $table->index('status');
            $table->index(['booking_id', 'option_type', 'status']);

            // Note: Check constraints will be handled at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_extra_options');
    }
};
