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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('discipline_id')->constrained('disciplines')->onDelete('restrict');
            // $table->foreignId('instructor_id')->constrained('instructors')->onDelete('restrict');
            // $table->foreignId('studio_id')->constrained('studios')->onDelete('restrict');
            $table->enum('type', ['presencial', 'en_vivo', 'grabada'])->default('presencial');
            $table->unsignedTinyInteger('duration_minutes');
            $table->unsignedTinyInteger('max_capacity');
            $table->text('description')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('all_levels');
            $table->string('music_genre', 100)->nullable();
            $table->text('special_requirements')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active');

            // nuevo
            $table->string('img_url')->nullable();


            $table->timestamps();

            // Índices
            $table->index(['discipline_id', 'status']);
            // $table->index(['instructor_id', 'status']);
            // $table->index('studio_id');
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
