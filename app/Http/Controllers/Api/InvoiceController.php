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
