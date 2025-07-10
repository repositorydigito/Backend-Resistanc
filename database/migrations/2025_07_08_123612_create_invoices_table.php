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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Datos principales de la factura
            $table->string('operacion')->nullable();
            $table->unsignedTinyInteger('tipo_de_comprobante');
            $table->string('serie');
            $table->unsignedInteger('numero');
            $table->unsignedTinyInteger('sunat_transaction')->nullable();
            $table->unsignedTinyInteger('cliente_tipo_de_documento');
            $table->string('cliente_numero_de_documento');
            $table->string('cliente_denominacion');
            $table->string('cliente_direccion')->nullable();
            $table->string('cliente_email')->nullable();
            $table->string('cliente_email_1')->nullable();
            $table->string('cliente_email_2')->nullable();
            $table->date('fecha_de_emision');
            $table->date('fecha_de_vencimiento')->nullable();
            $table->unsignedTinyInteger('moneda');
            $table->decimal('tipo_de_cambio', 10, 4)->nullable();
            $table->decimal('porcentaje_de_igv', 5, 2)->nullable();
            $table->decimal('descuento_global', 10, 2)->nullable();
            $table->decimal('total_descuento', 10, 2)->nullable();
            $table->decimal('total_anticipo', 10, 2)->nullable();
            $table->decimal('total_gravada', 10, 2)->nullable();
            $table->decimal('total_inafecta', 10, 2)->nullable();
            $table->decimal('total_exonerada', 10, 2)->nullable();
            $table->decimal('total_igv', 10, 2)->nullable();
            $table->decimal('total_gratuita', 10, 2)->nullable();
            $table->decimal('total_otros_cargos', 10, 2)->nullable();
            $table->decimal('total', 10, 2);
            $table->boolean('detraccion')->default(false);
            $table->text('observaciones')->nullable();

            // Arrays JSON
            // $table->json('items'); // Eliminado, ahora los items van en invoice_items
            $table->json('guias')->nullable();
            $table->json('venta_al_credito')->nullable();

            // Respuesta de Nubefact
            $table->string('enlace')->nullable();
            $table->string('enlace_del_pdf')->nullable();
            $table->string('enlace_del_xml')->nullable();
            $table->string('enlace_del_cdr')->nullable();
            $table->boolean('aceptada_por_sunat')->nullable();
            $table->string('sunat_description')->nullable();
            $table->string('sunat_note')->nullable();
            $table->string('sunat_responsecode')->nullable();
            $table->string('sunat_soap_error')->nullable();
            $table->text('cadena_para_codigo_qr')->nullable();
            $table->string('codigo_hash')->nullable();

            // Estado de envío
            $table->string('envio_estado')->default('pendiente')->comment('enviada, fallida, pendiente');
            $table->boolean('enviada_a_nubefact')->default(false);
            $table->text('error_envio')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
