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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Ej: 1CLASER, PAQUETE5R, PAQUETE40R, RSISTANC360');
            $table->string('slug', 100)->unique();
            $table->text('description');
            $table->string('short_description')->nullable();
            $table->unsignedInteger('classes_quantity');
            $table->decimal('price_soles', 8, 2);
            $table->decimal('original_price_soles', 8, 2)->nullable()->comment('Precio original para mostrar descuentos');
            $table->unsignedInteger('validity_days')->comment('Días de vigencia del paquete');
            $table->enum('package_type', ['presencial', 'virtual', 'mixto'])->default('presencial');
            $table->enum('billing_type', ['one_time', 'monthly', 'quarterly', 'yearly'])->default('one_time');
            $table->boolean('is_virtual_access')->default(false);
            $table->unsignedTinyInteger('priority_booking_days')->default(0)->comment('Días de anticipación para reservar');
            $table->boolean('auto_renewal')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->enum('status', ['active', 'inactive', 'coming_soon', 'discontinued'])->default('active');
            $table->unsignedTinyInteger('display_order')->default(0);
            $table->json('features')->nullable()->comment('Características y beneficios del paquete');
            $table->json('restrictions')->nullable()->comment('Restricciones y condiciones');
            $table->enum('target_audience', ['beginner', 'intermediate', 'advanced', 'all'])->default('all');
            $table->timestamps();

            // Índices
            $table->index(['status', 'display_order']);
            $table->index(['package_type', 'status']);
            $table->index('price_soles');
            $table->index(['is_featured', 'is_popular']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
