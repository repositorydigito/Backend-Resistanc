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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_package_id')->constrained('user_packages')->onDelete('restrict');
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->onDelete('restrict');
            $table->string('booking_reference', 20)->unique();
            $table->unsignedTinyInteger('companions_count')->default(0);
            $table->json('companion_details')->nullable()->comment('Información de acompañantes');
            $table->json('selected_seats')->nullable()->comment('Array de asientos seleccionados');
            $table->timestamp('booking_date')->useCurrent();
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('checkout_time')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show', 'waitlisted'])->default('pending');
            $table->enum('booking_type', ['presencial', 'virtual'])->default('presencial');
            $table->boolean('virtual_access_granted')->default(false);
            $table->timestamp('virtual_join_time')->nullable();
            $table->unsignedTinyInteger('virtual_duration_minutes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('cancellation_fee', 8, 2)->default(0.00);
            $table->unsignedTinyInteger('rating')->nullable()->comment('1-5 stars');
            $table->text('review_comment')->nullable();
            $table->boolean('has_extra_options')->default(false)->comment('Flag para optimizar consultas');
            $table->json('technical_issues')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['class_schedule_id', 'status']);
            $table->index('user_package_id');
            $table->index('booking_date');
            $table->index('status');
            $table->index('has_extra_options');

            // Note: Check constraints will be handled at the application level
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
