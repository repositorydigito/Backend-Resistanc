<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'operacion', 'tipo_de_comprobante', 'serie', 'numero', 'sunat_transaction',
        'cliente_tipo_de_documento', 'cliente_numero_de_documento', 'cliente_denominacion',
        'cliente_direccion', 'cliente_email', 'cliente_email_1', 'cliente_email_2',
        'fecha_de_emision', 'fecha_de_vencimiento', 'moneda', 'tipo_de_cambio',
        'porcentaje_de_igv', 'descuento_global', 'total_descuento', 'total_anticipo',
        'total_gravada', 'total_inafecta', 'total_exonerada', 'total_igv', 'total_gratuita',
        'total_otros_cargos', 'total', 'detraccion', 'observaciones',
        'guias', 'venta_al_credito',
        'enlace', 'enlace_del_pdf', 'enlace_del_xml', 'enlace_del_cdr',
        'aceptada_por_sunat', 'sunat_description', 'sunat_note', 'sunat_responsecode',
        'sunat_soap_error', 'cadena_para_codigo_qr', 'codigo_hash',
        'envio_estado', 'enviada_a_nubefact', 'error_envio',
    ];

    protected $casts = [
        'fecha_de_emision' => 'date',
        'fecha_de_vencimiento' => 'date',
        'detraccion' => 'boolean',
        'aceptada_por_sunat' => 'boolean',
        'guias' => 'array',
        'venta_al_credito' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
