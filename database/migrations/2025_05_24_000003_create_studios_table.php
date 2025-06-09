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
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('location');
            $table->unsignedTinyInteger('max_capacity');
            $table->json('equipment_available')->nullable();
            $table->json('amenities')->nullable()->comment('Vestuarios, duchas, etc.');
            $table->enum('studio_type', ['cycling', 'reformer', 'mat', 'multipurpose']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // nuevo
            $table->unsignedTinyInteger('capacity_per_seat')->nullable();
            $table->enum('addressing', ['right_to_left', 'left_to_right', 'center']);
            $table->integer('row')->nullable();
            $table->integer('column')->nullable();




            // Índices
            $table->index(['studio_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studios');
    }
};
