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
        Schema::create('disciplines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('cycling, solidreformer, pilates_mat');
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('color_hex', 7)->nullable()->comment('Color para UI (#FF5733)');
            $table->json('equipment_required')->nullable()->comment('Equipos necesarios');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('all_levels');
            $table->unsignedInteger('calories_per_hour_avg')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            // Índices
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disciplines');
    }
};
