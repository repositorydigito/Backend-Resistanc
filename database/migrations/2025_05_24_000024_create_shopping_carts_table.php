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
        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_id', 100)->nullable();
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->unsignedInteger('item_count')->default(0);

            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->timestamps();
            // Índices
            $table->index('user_id');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_carts');
    }
};
