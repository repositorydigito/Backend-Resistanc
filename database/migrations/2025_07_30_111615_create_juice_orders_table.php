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
        Schema::create('juice_orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique()->comment('Número de pedido único');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')->comment('ID del usuario que realiza el pedido');



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('juice_orders');
    }
};
