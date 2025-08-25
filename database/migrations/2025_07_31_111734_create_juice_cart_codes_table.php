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
        Schema::create('juice_cart_codes', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->boolean('is_used')->default(false);

            $table->foreignId('juice_order_id')
                ->nullable()
                ->constrained('juice_orders')
                ->onDelete('cascade')
                ->comment('ID del pedido asociado, si existe');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juice_cart_codes');
    }
};
