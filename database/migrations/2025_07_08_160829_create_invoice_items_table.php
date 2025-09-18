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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->onDelete('cascade')
                ->comment('ID de la factura a la que pertenece este ítem');

            $table->string('unidad_de_medida');
            $table->string('codigo');
            $table->string('descripcion');
            $table->integer('cantidad');
            $table->decimal('valor_unitario', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->integer('tipo_de_igv');
            $table->decimal('igv', 10, 2);
            $table->decimal('total', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
