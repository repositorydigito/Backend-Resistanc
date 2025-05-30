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
        Schema::table('packages', function (Blueprint $table) {

            $table->foreignId('membership_id')
                ->nullable()
                ->after('id')
                ->nullable()
                ->constrained('memberships')
                ->onDelete('set null');

            // Agregar índice para membership_id
            $table->index('membership_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Eliminar la foreign key constraint primero
            $table->dropForeign(['membership_id']);

            // Eliminar la columna
            $table->dropColumn('membership_id');
        });
    }
};
