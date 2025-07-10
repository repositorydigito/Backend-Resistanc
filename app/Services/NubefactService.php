<?php

namespace App\Services;

use App\Models\Company;
use Error;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NubefactService
{
    protected $apiUrl;
    protected $apiToken;

    public function __construct()
    {
        $this->initializeConfig();
    }

    private function initializeConfig()
    {
        $company = Company::first();

        if (!$company) {
            throw new Exception('No se encontró configuración de empresa');
        }

        $this->apiUrl = $company->url_facturacion;
        $this->apiToken = $company->token_facturacion;

        if (empty($this->apiUrl) || empty($this->apiToken)) {
            throw new Exception('URL o token de facturación no configurados');
        }
    }

    public function sendInvoice(array $data)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $this->apiToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, $data);

            if ($response->successful()) {
                return $this->successResponse($response->json());
            }

            return $this->errorResponse(
                $response->status(),
                'Error HTTP: ' . $response->status(),
                $response->json()
            );
        } catch (Exception $e) {
            Log::error('Error en NubefactService::sendInvoice', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);

            return $this->errorResponse(0, 'Error al enviar factura', $e->getMessage());
        }
    }

    private function successResponse($data)
    {
        return [
            'exito' => true,
            'codMensaje' => 1,
            'mensajeUsuario' => 'Factura enviada correctamente',
            'datoAdicional' => $data,
        ];
    }

    private function errorResponse($codigo, $mensaje, $datoAdicional = null)
    {
        return [
            'exito' => false,
            'codMensaje' => $codigo,
            'mensajeUsuario' => $mensaje,
            'datoAdicional' => $datoAdicional,
        ];
    }
}
