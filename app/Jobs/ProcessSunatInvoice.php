<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\SunatServices;
use CodersFree\LaravelGreenter\Facades\Greenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as LogFacade;

class ProcessSunatInvoice implements ShouldQueue
{
    use Queueable;

    public $tries = 3; // Intentar 3 veces antes de fallar
    public $backoff = [60, 300, 900]; // Esperar 1 min, 5 min, 15 min entre reintentos

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $invoiceId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SunatServices $sunatService): void
    {
        $invoice = Invoice::find($this->invoiceId);
        
        if (!$invoice) {
            \App\Models\Log::create([
                'user_id' => null,
                'action' => 'ProcessSunatInvoice - Error',
                'description' => 'Error al procesar factura',
                'data' => "No se encontró la factura con ID: {$this->invoiceId}",
            ]);
            return;
        }

        try {
            // Preparar datos para Greenter desde la factura guardada
            $data = $this->prepareDataFromInvoice($invoice);
            
            // Enviar a Greenter
            $response = Greenter::send('invoice', $data);
            
            // Verificar si la respuesta es válida
            if (!is_array($response)) {
                $response = [];
            }
            
            // Actualizar factura con la respuesta
            DB::beginTransaction();
            try {
                $invoice->update([
                    'envio_estado' => Invoice::ENVIO_ENVIADA,
                    'enviada_a_nubefact' => true,
                    'enlace_del_pdf' => $response['enlace_del_pdf'] ?? null,
                    'enlace_del_xml' => $response['enlace_del_xml'] ?? null,
                    'enlace_del_cdr' => $response['enlace_del_cdr'] ?? null,
                    'aceptada_por_sunat' => $response['aceptada_por_sunat'] ?? false,
                    'sunat_description' => $response['sunat_description'] ?? null,
                    'sunat_responsecode' => $response['sunat_responsecode'] ?? null,
                    'codigo_hash' => $response['codigo_hash'] ?? null,
                    'error_envio' => null,
                ]);
                
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Throwable $e) {
            // Actualizar factura con error
            $invoice->update([
                'envio_estado' => Invoice::ENVIO_FALLIDA,
                'error_envio' => $e->getMessage(),
            ]);
            
            // Log de error en base de datos
            \App\Models\Log::create([
                'user_id' => $invoice->user_id,
                'action' => 'ProcessSunatInvoice - Error',
                'description' => 'Error al procesar factura',
                'data' => $e->getMessage(),
            ]);
            
            // También log en Laravel
            LogFacade::error('Error al procesar factura en segundo plano', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            
            throw $e; // Re-lanzar para que el job falle y se reintente
        }
    }

    /**
     * Preparar datos para Greenter desde la factura guardada
     */
    protected function prepareDataFromInvoice(Invoice $invoice): array
    {
        $items = $invoice->items;
        $details = [];
        
        foreach ($items as $item) {
            $details[] = [
                "codProducto" => $item->codigo,
                "unidad" => $item->unidad_de_medida,
                "cantidad" => $item->cantidad,
                "mtoValorUnitario" => $item->valor_unitario,
                "descripcion" => $item->descripcion,
                "mtoBaseIgv" => $item->subtotal,
                "porcentajeIgv" => 18.00,
                "igv" => $item->igv,
                "tipAfeIgv" => (string) $item->tipo_de_igv,
                "totalImpuestos" => $item->igv,
                "mtoValorVenta" => $item->subtotal,
                "mtoPrecioUnitario" => $item->precio_unitario,
            ];
        }
        
        return [
            "ublVersion" => "2.1",
            "tipoOperacion" => "0101",
            "tipoDoc" => (string) str_pad($invoice->tipo_de_comprobante, 2, '0', STR_PAD_LEFT),
            "serie" => $invoice->serie,
            "correlativo" => (string) $invoice->numero,
            "fechaEmision" => $invoice->fecha_de_emision,
            "formaPago" => ['tipo' => 'Contado'],
            "tipoMoneda" => "PEN",
            "client" => [
                "tipoDoc" => (string) $invoice->cliente_tipo_de_documento,
                "numDoc" => $invoice->cliente_numero_de_documento,
                "rznSocial" => $invoice->cliente_denominacion,
            ],
            "mtoOperGravadas" => $invoice->total_gravada ?? 0,
            "mtoIGV" => $invoice->total_igv ?? 0,
            "totalImpuestos" => $invoice->total_igv ?? 0,
            "valorVenta" => $invoice->total_gravada ?? 0,
            "subTotal" => $invoice->total,
            "mtoImpVenta" => $invoice->total,
            "details" => $details,
        ];
    }
}
