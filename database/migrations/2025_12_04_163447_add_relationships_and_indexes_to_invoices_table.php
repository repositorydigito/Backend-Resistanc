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
            // Agregar relaciones
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuario (cliente) que generó el comprobante');

            $table->foreignId('order_id')
                ->nullable()
                ->after('user_id')
                ->constrained('orders')
                ->onDelete('set null')
                ->comment('Orden asociada al comprobante');

            // Agregar índices para mejorar rendimiento
            $table->index('tipo_de_comprobante', 'idx_invoices_tipo_comprobante');
            $table->index('serie', 'idx_invoices_serie');
            $table->index(['serie', 'numero'], 'idx_invoices_serie_numero');
            $table->index('cliente_numero_de_documento', 'idx_invoices_cliente_doc');
            $table->index('fecha_de_emision', 'idx_invoices_fecha_emision');
            $table->index('envio_estado', 'idx_invoices_envio_estado');
            $table->index('aceptada_por_sunat', 'idx_invoices_aceptada_sunat');
            $table->index(['user_id', 'tipo_de_comprobante'], 'idx_invoices_user_tipo');
            $table->index('created_at', 'idx_invoices_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex('idx_invoices_tipo_comprobante');
            $table->dropIndex('idx_invoices_serie');
            $table->dropIndex('idx_invoices_serie_numero');
            $table->dropIndex('idx_invoices_cliente_doc');
            $table->dropIndex('idx_invoices_fecha_emision');
            $table->dropIndex('idx_invoices_envio_estado');
            $table->dropIndex('idx_invoices_aceptada_sunat');
            $table->dropIndex('idx_invoices_user_tipo');
            $table->dropIndex('idx_invoices_created_at');

            // Eliminar relaciones
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn(['user_id', 'order_id']);
        });
    }
};
