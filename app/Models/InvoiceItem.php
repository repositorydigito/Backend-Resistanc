<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'unidad_de_medida',
        'codigo',
        'descripcion',
        'cantidad',
        'valor_unitario',
        'precio_unitario',
        'subtotal',
        'tipo_de_igv',
        'igv',
        'total',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
