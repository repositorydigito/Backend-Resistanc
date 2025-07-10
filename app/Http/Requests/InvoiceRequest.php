<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam operacion string required Operación a realizar. Example: generar_comprobante
 * @bodyParam tipo_de_comprobante integer required Tipo de comprobante. Example: 1
 * @bodyParam serie string required Serie del comprobante. Example: FFF1
 * @bodyParam numero integer required Número del comprobante. Example: 1
 * @bodyParam cliente_tipo_de_documento integer required Tipo de documento del cliente. Example: 6
 * @bodyParam cliente_numero_de_documento string required Número de documento del cliente. Example: 20600695771
 * @bodyParam cliente_denominacion string required Nombre o razón social del cliente. Example: NUBEFACT SA
 * @bodyParam fecha_de_emision string required Fecha de emisión (Y-m-d). Example: 2024-07-08
 * @bodyParam moneda integer required Moneda. Example: 1
 * @bodyParam total number required Total de la factura. Example: 708
 * @bodyParam items array required Lista de items del comprobante.
 * @bodyParam items[].unidad_de_medida string required Unidad de medida. Example: NIU
 * @bodyParam items[].codigo string required Código del producto. Example: 001
 * @bodyParam items[].descripcion string required Descripción del producto. Example: DETALLE DEL PRODUCTO
 * @bodyParam items[].cantidad integer required Cantidad. Example: 1
 * @bodyParam items[].valor_unitario number required Valor unitario. Example: 500
 * @bodyParam items[].precio_unitario number required Precio unitario. Example: 590
 * @bodyParam items[].subtotal number required Subtotal. Example: 500
 * @bodyParam items[].tipo_de_igv integer required Tipo de IGV. Example: 1
 * @bodyParam items[].igv number required IGV. Example: 90
 * @bodyParam items[].total number required Total del item. Example: 590
 */
class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operacion' => 'required|string',
            'tipo_de_comprobante' => 'required|integer|in:1,2',
            'serie' => 'required|string',
            'numero' => 'required|integer',
            'cliente_tipo_de_documento' => 'required|integer',
            'cliente_numero_de_documento' => 'required|string',
            'cliente_denominacion' => 'required|string',
            'fecha_de_emision' => 'required|date',
            'moneda' => 'required|integer',
            'total' => 'required|numeric',
            'items' => 'required|array',
            'items.*.unidad_de_medida' => 'required|string',
            'items.*.codigo' => 'required|string',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|integer',
            'items.*.valor_unitario' => 'required|numeric',
            'items.*.precio_unitario' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
            'items.*.tipo_de_igv' => 'required|integer',
            'items.*.igv' => 'required|numeric',
            'items.*.total' => 'required|numeric',
        ];
    }
}
