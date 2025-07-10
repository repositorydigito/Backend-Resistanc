<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NubefactService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Http\Requests\InvoiceRequest;

/**
 * @tags Facturación
 */
final class InvoiceController extends Controller
{
    /**
     * Genera y registra un comprobante electrónico
     *
     * Envía los datos del comprobante a Nubefact y guarda tanto la solicitud como la respuesta.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Generar comprobante electrónico
     * @operationId generarComprobante
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam operacion string Operación a realizar. Example: generar_comprobante
     * @bodyParam tipo_de_comprobante integer Tipo de comprobante. Example: 1
     * @bodyParam serie string Serie del comprobante. Example: FFF1
     * @bodyParam numero integer Número del comprobante. Example: 1
     * @bodyParam cliente_tipo_de_documento integer Tipo de documento del cliente. Example: 6
     * @bodyParam cliente_numero_de_documento string Número de documento del cliente. Example: 20600695771
     * @bodyParam cliente_denominacion string Nombre o razón social del cliente. Example: NUBEFACT SA
     * @bodyParam fecha_de_emision string Fecha de emisión (Y-m-d). Example: 2024-07-08
     * @bodyParam moneda integer Moneda. Example: 1
     * @bodyParam total number Total de la factura. Example: 708
     * @bodyParam items array Lista de items del comprobante.
     * @bodyParam items[].unidad_de_medida string Unidad de medida. Example: NIU
     * @bodyParam items[].codigo string Código del producto. Example: 001
     * @bodyParam items[].descripcion string Descripción del producto. Example: DETALLE DEL PRODUCTO
     * @bodyParam items[].cantidad integer Cantidad. Example: 1
     * @bodyParam items[].valor_unitario number Valor unitario. Example: 500
     * @bodyParam items[].precio_unitario number Precio unitario. Example: 590
     * @bodyParam items[].subtotal number Subtotal. Example: 500
     * @bodyParam items[].tipo_de_igv integer Tipo de IGV. Example: 1
     * @bodyParam items[].igv number IGV. Example: 90
     * @bodyParam items[].total number Total del item. Example: 590
     *
     * @response 200 {
     *   "tipo_de_comprobante": 1,
     *   "serie": "FFF1",
     *   "numero": 1,
     *   "enlace": "https://www.nubefact.com/cpe/uuid",
     *   "aceptada_por_sunat": true,
     *   "sunat_description": "La Factura numero FFF1-1, ha sido aceptada",
     *   "codigo_hash": "xMLFMnbgp1/bHEy572RKRTE9hPY="
     * }
     */
    public function generarComprobante(InvoiceRequest $request, NubefactService $nubefact)
    {



        $data = $request->all();
        $response = $nubefact->sendInvoice($data);

        // Guardar la factura y la respuesta
        $invoiceData = $data;
        unset($invoiceData['items']);
        $invoice = Invoice::create($invoiceData);

        // Guardar los items en la tabla relacionada
        foreach ($data['items'] as $item) {
            $invoice->items()->create($item);
        }

        // Guardar campos de la respuesta relevantes
        $invoice->enlace = $response['enlace'] ?? null;
        $invoice->enlace_del_pdf = $response['enlace_del_pdf'] ?? null;
        $invoice->enlace_del_xml = $response['enlace_del_xml'] ?? null;
        $invoice->enlace_del_cdr = $response['enlace_del_cdr'] ?? null;
        $invoice->aceptada_por_sunat = $response['aceptada_por_sunat'] ?? null;
        $invoice->sunat_description = $response['sunat_description'] ?? null;
        $invoice->sunat_note = $response['sunat_note'] ?? null;
        $invoice->sunat_responsecode = $response['sunat_responsecode'] ?? null;
        $invoice->sunat_soap_error = $response['sunat_soap_error'] ?? null;
        $invoice->cadena_para_codigo_qr = $response['cadena_para_codigo_qr'] ?? null;
        $invoice->codigo_hash = $response['codigo_hash'] ?? null;

        // Estado de envío y error
        if (($response['aceptada_por_sunat'] ?? false) || ($response['exito'] ?? false)) {
            $invoice->envio_estado = 'enviada';
            $invoice->enviada_a_nubefact = true;
            $invoice->error_envio = null;
        } else {
            // Extraer el error más relevante
            $mainMsg = $response['mensajeUsuario'] ?? '';
            $detailMsg = $response['datoAdicional']['errors'] ?? '';
            $errorCode = $response['datoAdicional']['codigo'] ?? ($response['codMensaje'] ?? '');
            $errorString = trim("{$mainMsg} | {$detailMsg} | Código: {$errorCode}", " |");

            $invoice->envio_estado = 'fallida';
            $invoice->enviada_a_nubefact = false;
            $invoice->error_envio = $errorString;
        }

        $invoice->save();

        return response()->json([
            'success' => $invoice->envio_estado === 'enviada',
            'invoice_id' => $invoice->id,
            'envio_estado' => $invoice->envio_estado,
            'enviada_a_nubefact' => $invoice->enviada_a_nubefact,
            'aceptada_por_sunat' => $invoice->aceptada_por_sunat,
            'enlace' => $invoice->enlace,
            'enlace_del_pdf' => $invoice->enlace_del_pdf,
            'enlace_del_xml' => $invoice->enlace_del_xml,
            'enlace_del_cdr' => $invoice->enlace_del_cdr,
            'sunat_description' => $invoice->sunat_description,
            'codigo_hash' => $invoice->codigo_hash,
            'error_envio' => $invoice->error_envio,
            'nubefact_response' => $response,
        ]);
    }
}
