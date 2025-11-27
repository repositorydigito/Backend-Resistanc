<?php

namespace App\Services;

use App\Models\Company;
use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company as GreenterCompany;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;
use NumberFormatter;

class SunatServices
{
    protected $company;
    protected $currentCorrelative;

    public function __construct()
    {
        $this->company = Company::first();

        if (!$this->company) {
            throw new \RuntimeException('No se encontró configuración de empresa');
        }
    }

    /**
     * Configura y retorna el objeto See para enviar factura a SUNAT
     */
    public function getSee()
    {
        if (!$this->company) {
            throw new \RuntimeException('No se encontró configuración de empresa');
        }

        $see = new See();

        // Usar certificado según el modo (producción o pruebas)
        $certPath = $this->company->is_production
            ? $this->company->cert_path_production
            : $this->company->cert_path_evidence;

        if (!$certPath || !Storage::exists($certPath)) {
            throw new \RuntimeException('Certificado digital no encontrado. Verifique la configuración de la empresa.');
        }

        $see->setCertificate(Storage::get($certPath));
        $see->setService($this->company->is_production ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);

        // Usar credenciales según el modo
        $solUser = $this->company->is_production
            ? $this->company->sol_user_production
            : $this->company->sol_user_evidence;

        $solPassword = $this->company->is_production
            ? $this->company->sol_user_password_production
            : $this->company->sol_user_password_evidence;

        if (!$solUser || !$solPassword) {
            throw new \RuntimeException('Credenciales SOL no configuradas. Verifique la configuración de la empresa.');
        }

        $see->setClaveSOL($this->company->ruc ?? '20000000001', $solUser, $solPassword);

        return $see;
    }

    /**
     * Genera una factura usando la información de la compañía desde la BD
     *
     * @param array $invoiceData Datos de la factura (cliente, detalles, montos, etc.)
     * @param int|null $correlative Número correlativo (opcional, se genera automáticamente si no se proporciona)
     * @return Invoice
     */
    public function getInvoice(array $invoiceData, ?int $correlative = null)
    {
        if (!$this->company) {
            throw new \RuntimeException('No se encontró configuración de empresa');
        }

        // Obtener serie configurada o usar por defecto
        $serie = $this->company->invoice_series ?? 'F001';

        // Obtener correlativo
        if ($correlative === null) {
            $this->currentCorrelative = $this->getNextCorrelative();
        } else {
            $this->currentCorrelative = $correlative;
        }

        // Crear factura
        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion($invoiceData['tipo_operacion'] ?? '0101') // Venta - Catalog. 51
            ->setTipoDoc($invoiceData['tipo_doc'] ?? '01') // Factura - Catalog. 01
            ->setSerie($serie)
            ->setCorrelativo((string) $this->currentCorrelative)
            ->setFechaEmision(new DateTime($invoiceData['fecha_emision'] ?? 'now'))
            ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
            ->setTipoMoneda($invoiceData['tipo_moneda'] ?? 'PEN') // Sol - Catalog. 02
            ->setCompany($this->getCompany())
            ->setClient($this->getClient($invoiceData['client'] ?? []))
            ->setMtoOperGravadas($invoiceData['mto_oper_gravadas'] ?? 0)
            ->setMtoIGV($invoiceData['mto_igv'] ?? 0)
            ->setTotalImpuestos($invoiceData['total_impuestos'] ?? 0)
            ->setValorVenta($invoiceData['valor_venta'] ?? 0)
            ->setSubTotal($invoiceData['sub_total'] ?? 0)
            ->setMtoImpVenta($invoiceData['mto_imp_venta'] ?? 0)
            ->setDetails($this->getDetails($invoiceData['details'] ?? []))
            ->setLegends($this->getLegends($invoiceData['total_amount'] ?? 0));

        return $invoice;
    }

    /**
     * Obtiene información de la compañía desde la BD
     */
    public function getCompany(): GreenterCompany
    {
        if (!$this->company->ruc) {
            throw new \RuntimeException('RUC de la empresa no configurado');
        }

        return (new GreenterCompany())
            ->setRuc($this->company->ruc)
            ->setRazonSocial($this->company->social_reason ?? $this->company->name)
            ->setNombreComercial($this->company->commercial_name ?? $this->company->name)
            ->setAddress($this->getAddress());
    }

    /**
     * Crea el objeto Client con datos proporcionados o por defecto
     */
    public function getClient(array $clientData = []): Client
    {
        return (new Client())
            ->setTipoDoc($clientData['tipo_doc'] ?? '6') // RUC por defecto
            ->setNumDoc($clientData['num_doc'] ?? '20000000001')
            ->setRznSocial($clientData['rzn_social'] ?? 'CLIENTE GENÉRICO');
    }

    /**
     * Obtiene la dirección de la compañía desde la BD
     */
    public function getAddress(): Address
    {
        return (new Address())
            ->setUbigueo($this->company->ubigeo ?? '150101')
            ->setDepartamento($this->company->department ?? 'LIMA')
            ->setProvincia($this->company->province ?? 'LIMA')
            ->setDistrito($this->company->district ?? 'LIMA')
            ->setUrbanizacion($this->company->urbanization ?? '-')
            ->setDireccion($this->company->address ?? '')
            ->setCodLocal($this->company->establishment_code ?? '0000');
    }

    /**
     * Obtiene los detalles de la factura
     */
    public function getDetails(array $detailsData = []): array
    {
        if (empty($detailsData)) {
            // Retornar detalle por defecto si no se proporcionan
            $item = (new SaleDetail())
                ->setCodProducto('P001')
                ->setUnidad('NIU')
                ->setCantidad(1)
                ->setMtoValorUnitario(100.00)
                ->setDescripcion('PRODUCTO/SERVICIO')
                ->setMtoBaseIgv(100.00)
                ->setPorcentajeIgv(18.00)
                ->setIgv(18.00)
                ->setTipAfeIgv('10')
                ->setTotalImpuestos(18.00)
                ->setMtoValorVenta(100.00)
                ->setMtoPrecioUnitario(118.00);

            return [$item];
        }

        // Construir detalles desde los datos proporcionados
        $details = [];
        foreach ($detailsData as $detail) {
            $item = (new SaleDetail())
                ->setCodProducto($detail['cod_producto'] ?? 'P001')
                ->setUnidad($detail['unidad'] ?? 'NIU')
                ->setCantidad($detail['cantidad'] ?? 1)
                ->setMtoValorUnitario($detail['mto_valor_unitario'] ?? 0)
                ->setDescripcion($detail['descripcion'] ?? '')
                ->setMtoBaseIgv($detail['mto_base_igv'] ?? 0)
                ->setPorcentajeIgv($detail['porcentaje_igv'] ?? 18.00)
                ->setIgv($detail['igv'] ?? 0)
                ->setTipAfeIgv($detail['tip_afe_igv'] ?? '10')
                ->setTotalImpuestos($detail['total_impuestos'] ?? 0)
                ->setMtoValorVenta($detail['mto_valor_venta'] ?? 0)
                ->setMtoPrecioUnitario($detail['mto_precio_unitario'] ?? 0);

            $details[] = $item;
        }

        return $details;
    }

    /**
     * Genera las leyendas de la factura
     */
    public function getLegends(float $totalAmount = 0): array
    {
        $amountInWords = $this->numberToWords($totalAmount);

        $legend = (new Legend())
            ->setCode('1000') // Monto en letras - Catalog. 52
            ->setValue($amountInWords);

        return [$legend];
    }

    /**
     * Convierte un número a palabras en español (Perú)
     */
    protected function numberToWords(float $number): string
    {
        $formatter = new NumberFormatter('es_PE', NumberFormatter::SPELLOUT);
        $entero = floor($number);
        $decimal = round(($number - $entero) * 100);

        $words = $formatter->format($entero);
        $words = mb_strtoupper(mb_substr($words, 0, 1)) . mb_substr($words, 1);

        return "SON {$words} CON " . str_pad($decimal, 2, '0', STR_PAD_LEFT) . "/100 SOLES";
    }

    /**
     * Obtiene el siguiente correlativo disponible
     */
    protected function getNextCorrelative(): int
    {
        $initialCorrelative = $this->company->invoice_initial_correlative ?? 1;

        // Por ahora retorna el inicial, pero debería consultar el último usado
        // y devolver el siguiente. Esto se puede implementar con una tabla o archivo
        // que almacene el último correlativo usado por serie.

        return $initialCorrelative;
    }

    /**
     * Procesa la respuesta de SUNAT
     */
    public function sunatResponse($result): array
    {
        $response = [
            'success' => $result->isSuccess(),
        ];

        if (!$response['success']) {
            // Error al conectarse a SUNAT
            $error = $result->getError();
            $response['error'] = [
                'code' => $error->getCode(),
                'message' => $error->getMessage()
            ];
            return $response;
        }

        // Éxito
        $response['cdrZip'] = base64_encode($result->getCdrZip());
        $cdr = $result->getCdrResponse();

        $response['cdrResponse'] = [
            'code' => (int) $cdr->getCode(),
            'description' => $cdr->getDescription(),
            'notes' => $cdr->getNotes(),
        ];

        return $response;
    }

    /**
     * Envía la factura a SUNAT
     */
    public function sendInvoice(array $invoiceData, ?int $correlative = null): array
    {
        try {
            $see = $this->getSee();
            $invoice = $this->getInvoice($invoiceData, $correlative);

            $result = $see->send($invoice);

            return $this->sunatResponse($result);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => [
                    'code' => 'EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }
}
