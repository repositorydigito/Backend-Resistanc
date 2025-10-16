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
        Schema::table('user_packages', function (Blueprint $table) {
            $table->foreignId('gift_order_id')
                ->nullable()
                ->constrained('juice_orders')
                ->onDelete('set null')
                ->comment('ID del pedido de regalo asociado (shakes gratis por membresía)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->dropForeign(['gift_order_id']);
            $table->dropColumn('gift_order_id');
        });
    }
};
