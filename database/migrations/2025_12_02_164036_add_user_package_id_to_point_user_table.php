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
        Schema::table('point_user', function (Blueprint $table) {
            $table->foreignId('user_package_id')
                ->nullable()
                ->after('package_id')
                ->constrained('user_packages')
                ->onDelete('set null')
                ->comment('ID del paquete específico del usuario (UserPackage) del que provienen los puntos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_user', function (Blueprint $table) {
            $table->dropForeign(['user_package_id']);
            $table->dropColumn('user_package_id');
        });
    }
};
