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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('effective_completed_classes')
                ->default(0)
                ->after('code')
                ->comment('Clases completadas efectivas: incluye clases físicamente completadas + clases otorgadas por membresías');
        });

        // Calcular el valor inicial para usuarios existentes
        // Esto se hace en un comando separado para no bloquear la migración
        // Se ejecutará después de la migración
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('effective_completed_classes');
        });
    }
};
