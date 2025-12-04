<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    // Constantes para tipos de comprobante según SUNAT
    const TIPO_FACTURA = 1;        // Factura
    const TIPO_BOLETA = 3;         // Boleta de Venta
    const TIPO_NOTA_CREDITO_FACTURA = 7;  // Nota de Crédito que se relaciona con una Factura
    const TIPO_NOTA_CREDITO_BOLETA = 8;   // Nota de Crédito que se relaciona con una Boleta
    const TIPO_NOTA_DEBITO_FACTURA = 5;   // Nota de Débito que se relaciona con una Factura
    const TIPO_NOTA_DEBITO_BOLETA = 6;    // Nota de Débito que se relaciona con una Boleta

    // Constantes para estado de envío
    const ENVIO_PENDIENTE = 'pendiente';
    const ENVIO_ENVIADA = 'enviada';
    const ENVIO_FALLIDA = 'fallida';

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
        'user_id', 'order_id', 'user_package_id', // Relaciones con User, Order y UserPackage
    ];

    protected $casts = [
        'fecha_de_emision' => 'date',
        'fecha_de_vencimiento' => 'date',
        'detraccion' => 'boolean',
        'aceptada_por_sunat' => 'boolean',
        'enviada_a_nubefact' => 'boolean',
        'guias' => 'array',
        'venta_al_credito' => 'array',
    ];

    /**
     * Relación con los items del comprobante
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Relación con el usuario (cliente)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la orden asociada
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación con el paquete del usuario asociado
     */
    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class);
    }

    /**
     * Scope para filtrar facturas
     */
    public function scopeFacturas($query)
    {
        return $query->where('tipo_de_comprobante', self::TIPO_FACTURA);
    }

    /**
     * Scope para filtrar boletas
     */
    public function scopeBoletas($query)
    {
        return $query->where('tipo_de_comprobante', self::TIPO_BOLETA);
    }

    /**
     * Scope para filtrar comprobantes aceptados por SUNAT
     */
    public function scopeAceptados($query)
    {
        return $query->where('aceptada_por_sunat', true);
    }

    /**
     * Scope para filtrar comprobantes pendientes de envío
     */
    public function scopePendientes($query)
    {
        return $query->where('envio_estado', self::ENVIO_PENDIENTE);
    }

    /**
     * Verificar si es una factura
     */
    public function isFactura(): bool
    {
        return $this->tipo_de_comprobante === self::TIPO_FACTURA;
    }

    /**
     * Verificar si es una boleta
     */
    public function isBoleta(): bool
    {
        return $this->tipo_de_comprobante === self::TIPO_BOLETA;
    }

    /**
     * Obtener el nombre del tipo de comprobante
     */
    public function getTipoComprobanteNombreAttribute(): string
    {
        return match($this->tipo_de_comprobante) {
            self::TIPO_FACTURA => 'Factura',
            self::TIPO_BOLETA => 'Boleta de Venta',
            self::TIPO_NOTA_CREDITO_FACTURA => 'Nota de Crédito - Factura',
            self::TIPO_NOTA_CREDITO_BOLETA => 'Nota de Crédito - Boleta',
            self::TIPO_NOTA_DEBITO_FACTURA => 'Nota de Débito - Factura',
            self::TIPO_NOTA_DEBITO_BOLETA => 'Nota de Débito - Boleta',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener el número completo del comprobante (serie-número)
     */
    public function getNumeroCompletoAttribute(): string
    {
        return "{$this->serie}-{$this->numero}";
    }
}
