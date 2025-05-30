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
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('level', ['resistance', 'gold', 'black'])->unique();
            $table->text('description')->nullable();
            $table->json('benefits');
            $table->string('color_hex')->default('#d4691a');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('display_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
