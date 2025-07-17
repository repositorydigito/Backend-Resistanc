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
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();

            $table->string('package_code', 20)->unique()->comment('Código único del paquete de clases');
            $table->unsignedInteger('total_classes')->comment('Número total de clases incluidas en el paquete');
            $table->unsignedInteger('used_classes')->default(0)->comment('Número de clases utilizadas');
            $table->unsignedInteger('remaining_classes')->default(0)->comment('Número de clases restantes');
            $table->decimal('amount_paid_soles', 8, 2)->comment('Monto pagado en soles');
            $table->char('currency', 3)->default('PEN')->comment('Código de moneda');
            $table->date('purchase_date')->comment('Fecha de compra');
            $table->date('activation_date')->nullable()->comment('Fecha de activación');
            $table->date('expiry_date')->comment('Fecha de expiración');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled', 'suspended'])->default('pending')->comment('Estado del paquete');
            $table->boolean('auto_renew')->default(false)->comment('Indica si la renovación es automática');
            $table->decimal('renewal_price', 8, 2)->nullable()->comment('Precio de renovación');
            $table->json('benefits_included')->nullable()->comment('Beneficios específicos incluidos');
            $table->text('notes')->nullable()->comment('Notas adicionales sobre el paquete');

            // Relaciones
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('package_id')->constrained('packages')->onDelete('restrict');

            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['package_id', 'status']);
            $table->index('expiry_date');
            $table->index('package_code');
            $table->index(['user_id', 'expiry_date', 'status']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_packages');
    }
};
