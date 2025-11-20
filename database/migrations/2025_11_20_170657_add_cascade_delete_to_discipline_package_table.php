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
        Schema::table('discipline_package', function (Blueprint $table) {
            // Eliminar la clave foránea existente
            $table->dropForeign(['package_id']);
            
            // Recrear la clave foránea con cascade delete
            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->onDelete('cascade')
                ->comment('Paquete asociada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discipline_package', function (Blueprint $table) {
            // Eliminar la clave foránea con cascade
            $table->dropForeign(['package_id']);
            
            // Restaurar la clave foránea original sin cascade
            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->comment('Paquete asociada');
        });
    }
};
