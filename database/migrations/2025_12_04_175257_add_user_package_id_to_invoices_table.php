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
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_package_id')
                ->nullable()
                ->after('order_id')
                ->constrained('user_packages')
                ->onDelete('set null')
                ->comment('Paquete del usuario asociado al comprobante');
            
            $table->index('user_package_id', 'idx_invoices_user_package');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_user_package');
            $table->dropForeign(['user_package_id']);
            $table->dropColumn('user_package_id');
        });
    }
};
