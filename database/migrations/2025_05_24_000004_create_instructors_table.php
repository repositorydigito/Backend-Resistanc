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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Si el instructor tiene cuenta de usuario');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 15)->nullable();
            $table->json('specialties')->comment('Array de discipline IDs');
            $table->text('bio')->nullable();
            $table->json('certifications')->nullable()->comment('Certificaciones y títulos');
            $table->string('profile_image')->nullable();
            $table->string('instagram_handle', 100)->nullable();
            $table->boolean('is_head_coach')->default(false);
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->unsignedInteger('total_classes_taught')->default(0);
            $table->date('hire_date')->nullable();
            $table->decimal('hourly_rate_soles', 8, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave', 'terminated'])->default('active');
            $table->json('availability_schedule')->nullable()->comment('Horarios disponibles por día');
            $table->timestamps();

            // Índices
            $table->index('status');
            $table->index(['is_head_coach', 'status']);
            $table->index('rating_average');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
