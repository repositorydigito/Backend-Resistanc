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
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('favoritable_type'); // Store the type of the favoritable model
            $table->string('favoritable_id'); // Store the ID of the favoritable model

            $table->text('notes')->nullable(); // Optional notes for the favorite item
            $table->integer('priority')->default(0); // Priority level for the favorite item

            // Additional fields can be added as needed

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'user_favorites_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
