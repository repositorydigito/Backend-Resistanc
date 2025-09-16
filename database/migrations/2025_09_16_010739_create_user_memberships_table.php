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
        Schema::create('user_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('membership_id')->constrained()->onDelete('cascade');
            $table->foreignId('discipline_id')->nullable()->constrained()->onDelete('set null');

            // Clases gratis de la membresía
            $table->integer('total_free_classes')->default(0)->comment('Total de clases gratis otorgadas');
            $table->integer('used_free_classes')->default(0)->comment('Clases gratis utilizadas');
            $table->integer('remaining_free_classes')->default(0)->comment('Clases gratis restantes');

            // Fechas
            $table->date('activation_date')->nullable()->comment('Fecha de activación de la membresía');
            $table->date('expiry_date')->nullable()->comment('Fecha de expiración de la membresía');

            // Estado
            $table->enum('status', ['active', 'expired', 'pending', 'suspended', 'cancelled'])
                  ->default('active')
                  ->comment('Estado de la membresía');

            // Referencia al paquete que otorgó esta membresía
            $table->foreignId('source_package_id')->nullable()->constrained('packages')->onDelete('set null')
                  ->comment('ID del paquete que otorgó esta membresía');

            // Notas adicionales
            $table->text('notes')->nullable()->comment('Notas adicionales sobre la membresía');

            $table->timestamps();

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'discipline_id', 'status']);
            $table->index(['expiry_date']);
            $table->index(['source_package_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_memberships');
    }
};
