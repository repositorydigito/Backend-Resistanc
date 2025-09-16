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
        Schema::table('class_schedule_seat', function (Blueprint $table) {
            $table->foreignId('user_membership_id')
                  ->nullable()
                  ->after('user_package_id')
                  ->constrained('user_memberships')
                  ->onDelete('set null')
                  ->comment('ID de la membresía utilizada para esta reserva');

            // Índice para mejorar consultas
            $table->index(['user_membership_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_schedule_seat', function (Blueprint $table) {
            $table->dropIndex(['user_membership_id', 'status']);
            $table->dropForeign(['user_membership_id']);
            $table->dropColumn('user_membership_id');
        });
    }
};
