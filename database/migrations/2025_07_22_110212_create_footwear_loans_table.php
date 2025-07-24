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
        Schema::create('footwear_loans', function (Blueprint $table) {
            $table->id();

            $table->dateTime('loan_date')->useCurrent()->comment('Fecha de préstamo');
            $table->dateTime('estimated_return_date')->nullable()->comment('Fecha estimada de devolución');
            $table->dateTime('return_date')->nullable();
            $table->enum('status', ['in_use', 'returned', 'overdue', 'lost'])->default('in_use')->comment('Estado del préstamo');
            $table->text('notes')->nullable();

            // Relaciones
            $table->foreignId('footwear_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_client_id')->constrained('users')->comment('Cliente que recibe el préstamo');
            $table->foreignId('user_id')->nullable()->constrained()->comment('Usuario que gestiona el préstamo');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footwear_loans');
    }
};
