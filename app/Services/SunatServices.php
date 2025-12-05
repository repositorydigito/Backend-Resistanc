<?php

namespace App\Services;

use App\Jobs\ProcessSunatInvoice;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Log as LogModel;
use App\Models\User;
use CodersFree\LaravelGreenter\Facades\Greenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SunatServices
{
    /**
     * Verificar si el procesamiento debe ser instantáneo o en segundo plano
     */
    protected function isInstantMode(): bool
    {
        return env('SUNAT_PROCESS_MODE', 'instant') === 'instant';
    }

    /**
     * Guardar log de error en base de datos
     * 
     * @param string $action Acción que generó el error
     * @param string $description Descripción del error
     * @param string|array|object $errorData Mensaje de error o array/objeto con información detallada
     * @param int|null $userId ID del usuario (opcional)
     * @param array $additionalData Información adicional a incluir en el log
     */
    protected function logError(string $action, string $description, $errorData, ?int $userId = null, array $additionalData = []): void
    {
        try {
            // Normalizar los datos del error
            $normalizedData = [];
            
            // Si errorData es un string, convertirlo a array
            if (is_string($errorData)) {
                $normalizedData['error_message'] = $errorData;
            } elseif (is_array($errorData) || is_object($errorData)) {
                // Si es array u objeto, usar directamente
                $normalizedData = is_object($errorData) ? (array) $errorData : $errorData;
            } else {
                // Cualquier otro tipo, convertirlo a string
                $normalizedData['error_message'] = (string) $errorData;
            }
            
            // Agregar información adicional si se proporciona
            if (!empty($additionalData)) {
                $normalizedData = array_merge($normalizedData, $additionalData);
            }
            
            // Agregar timestamp para mejor rastreabilidad
            $normalizedData['logged_at'] = now()->toIso8601String();
            
            LogModel::create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'data' => $normalizedData,
            ]);
        } catch (\Exception $e) {
            // Si falla el log, al menos registrar en Laravel
            Log::error('Error al guardar log en base de datos', [
                'error' => $e->getMessage(),
                'original_action' => $action,
                'original_description' => $description,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Obtener la empresa (configuración)
     */
    protected function getCompany(): Company
    {
        $company = Company::first();
        
        if (!$company) {
            throw new \Exception('No se encontró la configuración de la empresa');
        }
        
        return $company;
    }

    /**
     * Obtener y actualizar el siguiente correlativo para boletas
     */
    protected function getNextBoletaCorrelative(Company $company): int
    {
        DB::beginTransaction();
        try {
            $correlativo = $company->boleta_initial_correlative;
            $company->increment('boleta_initial_correlative');
            DB::commit();
            return $correlativo;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener y actualizar el siguiente correlativo para facturas
     */
    protected function getNextFacturaCorrelative(Company $company): int
    {
        DB::beginTransaction();
        try {
            $correlativo = $company->invoice_initial_correlative;
            $company->increment('invoice_initial_correlative');
            DB::commit();
            return $correlativo;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Formatear número a texto (para leyendas)
     */
    protected function numberToWords(float $number): string
    {
        // Implementación básica - puedes usar una librería más completa
        $entero = (int) $number;
        $decimal = (int) (($number - $entero) * 100);
        
        $texto = $this->convertNumberToWords($entero);
        $texto .= " CON " . str_pad($decimal, 2, '0', STR_PAD_LEFT) . "/100 SOLES";
        
        return strtoupper($texto);
    }

    /**
     * Convertir número a palabras (implementación básica)
     */
    protected function convertNumberToWords(int $number): string
    {
        // Implementación simplificada - considera usar una librería como "numalet" para español
        if ($number == 0) return 'CERO';
        if ($number == 100) return 'CIEN';
        if ($number < 100) {
            $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
            $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
            $especiales = [11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE'];
            
            if (isset($especiales[$number])) {
                return $especiales[$number];
            }
            
            $decena = (int)($number / 10);
            $unidad = $number % 10;
            
            if ($decena == 0) return $unidades[$unidad];
            if ($unidad == 0) return $decenas[$decena];
            
            return $decenas[$decena] . ' Y ' . $unidades[$unidad];
        }
        
        return 'CIEN'; // Simplificado para números mayores
    }

    /**
     * Generar una boleta de venta
     * 
     * @param array $clientData Datos del cliente ['tipoDoc' => '1', 'numDoc' => '12345678', 'rznSocial' => 'Nombre', 'direccion' => 'Dirección', 'email' => 'email@example.com']
     * @param array $items Array de items [['codProducto' => 'COD001', 'unidad' => 'NIU', 'cantidad' => 1, 'descripcion' => 'Descripción', 'mtoValorUnitario' => 84.75, 'mtoPrecioUnitario' => 100.00]]
     * @param int|null $userId ID del usuario (opcional)
     * @param int|null $orderId ID de la orden o user_package_id (opcional)
     * @param int|null $userPackageId ID del paquete del usuario (opcional, si se proporciona se usa en lugar de orderId)
     * @return array
     */
    public function generarBoleta(array $clientData, array $items, ?int $userId = null, ?int $orderId = null, ?int $userPackageId = null): array
    {
        try {
            $company = $this->getCompany();
            
            // Obtener siguiente correlativo
            $correlativo = $this->getNextBoletaCorrelative($company);
            $serie = $company->boleta_series ?? 'B001';
            
            // Calcular totales
            $mtoOperGravadas = 0;
            $mtoIGV = 0;
            $totalImpuestos = 0;
            $valorVenta = 0;
            $subTotal = 0;
            $mtoImpVenta = 0;
            
            $details = [];
            foreach ($items as $item) {
                $mtoValorUnitario = $item['mtoValorUnitario'] ?? 0;
                $mtoPrecioUnitario = $item['mtoPrecioUnitario'] ?? $mtoValorUnitario * 1.18;
                $cantidad = $item['cantidad'] ?? 1;
                $igv = ($mtoPrecioUnitario - $mtoValorUnitario) * $cantidad;
                
                $mtoOperGravadas += $mtoValorUnitario * $cantidad;
                $mtoIGV += $igv;
                $valorVenta += $mtoValorUnitario * $cantidad;
                $subTotal += $mtoPrecioUnitario * $cantidad;
                
                $details[] = [
                    "codProducto" => $item['codProducto'] ?? 'PROD001',
                    "unidad" => $item['unidad'] ?? 'NIU',
                    "cantidad" => $cantidad,
                    "mtoValorUnitario" => $mtoValorUnitario,
                    "descripcion" => $item['descripcion'] ?? 'Producto',
                    "mtoBaseIgv" => $mtoValorUnitario * $cantidad,
                    "porcentajeIgv" => 18.00,
                    "igv" => $igv,
                    "tipAfeIgv" => "10",
                    "totalImpuestos" => $igv,
                    "mtoValorVenta" => $mtoValorUnitario * $cantidad,
                    "mtoPrecioUnitario" => $mtoPrecioUnitario,
                ];
            }
            
            $totalImpuestos = $mtoIGV;
            $mtoImpVenta = $subTotal;
            
            // Preparar datos para Greenter
            $data = [
                "ublVersion" => "2.1",
                "tipoOperacion" => "0101",
                "tipoDoc" => "03", // Boleta
                "serie" => $serie,
                "correlativo" => (string) $correlativo,
                "fechaEmision" => now(),
                "formaPago" => ['tipo' => 'Contado'],
                "tipoMoneda" => "PEN",
                "client" => [
                    "tipoDoc" => $clientData['tipoDoc'] ?? '1',
                    "numDoc" => $clientData['numDoc'] ?? '',
                    "rznSocial" => $clientData['rznSocial'] ?? '',
                ],
                "mtoOperGravadas" => round($mtoOperGravadas, 2),
                "mtoIGV" => round($mtoIGV, 2),
                "totalImpuestos" => round($totalImpuestos, 2),
                "valorVenta" => round($valorVenta, 2),
                "subTotal" => round($subTotal, 2),
                "mtoImpVenta" => round($mtoImpVenta, 2),
                "details" => $details,
                "legends" => [
                    [
                        "code" => "1000",
                        "value" => $this->numberToWords($mtoImpVenta),
                    ],
                ],
            ];
            
            // Guardar en base de datos primero (con estado pendiente si es en segundo plano)
            $envioEstado = $this->isInstantMode() ? Invoice::ENVIO_PENDIENTE : Invoice::ENVIO_PENDIENTE;
            $invoice = $this->guardarComprobante($data, [], Invoice::TIPO_BOLETA, $userId, $orderId, $items, $envioEstado, $userPackageId);
            
            // Procesar según el modo configurado
            if ($this->isInstantMode()) {
                // Modo instantáneo: procesar inmediatamente
                try {
                    $response = Greenter::send('invoice', $data);
                    
                    // Verificar si la respuesta es válida
                    if (!is_array($response)) {
                        $response = [];
                    }
                    
                    // Actualizar factura con la respuesta
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
                    ]);
                    
                } catch (\Throwable $e) {
                    // Obtener información detallada del error
                    $errorDetails = [
                        'error_message' => $e->getMessage(),
                        'error_line' => $e->getLine(),
                        'error_file' => $e->getFile(),
                        'serie' => $serie,
                        'correlativo' => $correlativo,
                        'tipo_comprobante' => 'Boleta',
                    ];
                    
                    // Si hay respuesta de Greenter, incluirla
                    if (isset($response) && is_array($response)) {
                        $errorDetails['greenter_response'] = $response;
                    }
                    
                    // Actualizar factura con error
                    $invoice->update([
                        'envio_estado' => Invoice::ENVIO_FALLIDA,
                        'error_envio' => $e->getMessage(),
                    ]);
                    
                    // Log de error en base de datos
                    $this->logError(
                        'Generar Boleta - Error',
                        "Error al generar boleta {$serie}-{$correlativo}",
                        $errorDetails,
                        $userId,
                        [
                            'invoice_id' => $invoice->id,
                            'user_package_id' => $userPackageId,
                            'order_id' => $orderId,
                        ]
                    );
                    
                    throw $e;
                }
            } else {
                // Modo en segundo plano: despachar job
                ProcessSunatInvoice::dispatch($invoice->id);
            }
            
            return [
                'success' => true,
                'message' => $this->isInstantMode() ? 'Boleta generada exitosamente' : 'Boleta creada, procesándose en segundo plano',
                'data' => [
                    'id' => $invoice->id,
                    'serie' => $serie,
                    'numero' => $correlativo,
                    'numero_completo' => "{$serie}-{$correlativo}",
                    'cliente' => $clientData['rznSocial'] ?? '',
                    'total' => $mtoImpVenta,
                    'fecha' => now()->format('d/m/Y'),
                    'enlace_pdf' => $invoice->enlace_del_pdf,
                    'enlace_xml' => $invoice->enlace_del_xml,
                    'aceptada_por_sunat' => $invoice->aceptada_por_sunat,
                    'envio_estado' => $invoice->envio_estado,
                    'procesado_instantaneo' => $this->isInstantMode(),
                ]
            ];
            
        } catch (\Throwable $e) {
            // Log de error en base de datos
            $this->logError(
                'Generar Boleta - Error General',
                'Error al generar boleta',
                [
                    'error_message' => $e->getMessage(),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ],
                $userId,
                [
                    'user_package_id' => $userPackageId,
                    'order_id' => $orderId,
                ]
            );
            
            // También log en Laravel
            Log::error('Error al generar boleta', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Generar una factura
     * 
     * @param array $clientData Datos del cliente
     * @param array $items Array de items
     * @param int|null $userId ID del usuario (opcional)
     * @param int|null $orderId ID de la orden o user_package_id (opcional)
     * @param int|null $userPackageId ID del paquete del usuario (opcional, si se proporciona se usa en lugar de orderId)
     * @return array
     */
    public function generarFactura(array $clientData, array $items, ?int $userId = null, ?int $orderId = null, ?int $userPackageId = null): array
    {
        try {
            $company = $this->getCompany();
            
            // Obtener siguiente correlativo
            $correlativo = $this->getNextFacturaCorrelative($company);
            $serie = $company->invoice_series ?? 'F001';
            
            // Calcular totales (mismo proceso que boletas)
            $mtoOperGravadas = 0;
            $mtoIGV = 0;
            $totalImpuestos = 0;
            $valorVenta = 0;
            $subTotal = 0;
            $mtoImpVenta = 0;
            
            $details = [];
            foreach ($items as $item) {
                $mtoValorUnitario = $item['mtoValorUnitario'] ?? 0;
                $mtoPrecioUnitario = $item['mtoPrecioUnitario'] ?? $mtoValorUnitario * 1.18;
                $cantidad = $item['cantidad'] ?? 1;
                $igv = ($mtoPrecioUnitario - $mtoValorUnitario) * $cantidad;
                
                $mtoOperGravadas += $mtoValorUnitario * $cantidad;
                $mtoIGV += $igv;
                $valorVenta += $mtoValorUnitario * $cantidad;
                $subTotal += $mtoPrecioUnitario * $cantidad;
                
                $details[] = [
                    "codProducto" => $item['codProducto'] ?? 'PROD001',
                    "unidad" => $item['unidad'] ?? 'NIU',
                    "cantidad" => $cantidad,
                    "mtoValorUnitario" => $mtoValorUnitario,
                    "descripcion" => $item['descripcion'] ?? 'Producto',
                    "mtoBaseIgv" => $mtoValorUnitario * $cantidad,
                    "porcentajeIgv" => 18.00,
                    "igv" => $igv,
                    "tipAfeIgv" => "10",
                    "totalImpuestos" => $igv,
                    "mtoValorVenta" => $mtoValorUnitario * $cantidad,
                    "mtoPrecioUnitario" => $mtoPrecioUnitario,
                ];
            }
            
            $totalImpuestos = $mtoIGV;
            $mtoImpVenta = $subTotal;
            
            // Validar que los datos del cliente estén completos para facturas
            if (empty($clientData['tipoDoc']) || $clientData['tipoDoc'] !== '6') {
                throw new \Exception('Para generar una factura, el tipo de documento debe ser RUC (6). Tipo recibido: ' . ($clientData['tipoDoc'] ?? 'vacío'));
            }
            
            if (empty($clientData['numDoc']) || strlen($clientData['numDoc']) !== 11) {
                throw new \Exception('Para generar una factura, el RUC es obligatorio y debe tener 11 dígitos. RUC recibido: ' . ($clientData['numDoc'] ?? 'vacío'));
            }
            
            if (empty($clientData['rznSocial'])) {
                throw new \Exception('Para generar una factura, la razón social es obligatoria.');
            }
            
            // Preparar datos para Greenter
            $data = [
                "ublVersion" => "2.1",
                "tipoOperacion" => "0101",
                "tipoDoc" => "01", // Factura
                "serie" => $serie,
                "correlativo" => (string) $correlativo,
                "fechaEmision" => now(),
                "formaPago" => ['tipo' => 'Contado'],
                "tipoMoneda" => "PEN",
                "client" => [
                    "tipoDoc" => $clientData['tipoDoc'], // RUC (6) para facturas - obligatorio
                    "numDoc" => $clientData['numDoc'], // RUC - obligatorio
                    "rznSocial" => $clientData['rznSocial'], // Razón social - obligatorio
                    "direccion" => $clientData['direccion'] ?? '', // Dirección fiscal
                ],
                "mtoOperGravadas" => round($mtoOperGravadas, 2),
                "mtoIGV" => round($mtoIGV, 2),
                "totalImpuestos" => round($totalImpuestos, 2),
                "valorVenta" => round($valorVenta, 2),
                "subTotal" => round($subTotal, 2),
                "mtoImpVenta" => round($mtoImpVenta, 2),
                "details" => $details,
                "legends" => [
                    [
                        "code" => "1000",
                        "value" => $this->numberToWords($mtoImpVenta),
                    ],
                ],
            ];

            // Guardar en base de datos primero (con estado pendiente)
            $invoice = $this->guardarComprobante($data, [], Invoice::TIPO_FACTURA, $userId, $orderId, $items, Invoice::ENVIO_PENDIENTE, $userPackageId);
            
            // Procesar según el modo configurado
            if ($this->isInstantMode()) {
                // Modo instantáneo: procesar inmediatamente
                try {
            $response = Greenter::send('invoice', $data);

                    // Verificar si la respuesta es válida
                    if (!is_array($response)) {
                        $response = [];
                    }
                    
                    // Actualizar factura con la respuesta
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
                    ]);
                    
                } catch (\Throwable $e) {
                    // Extraer mensaje de error más claro
                    $errorMessage = $e->getMessage();
                    
                    // Preparar detalles del error
                    $errorDetails = [
                        'error_message' => $errorMessage,
                        'error_line' => $e->getLine(),
                        'error_file' => $e->getFile(),
                        'serie' => $serie,
                        'correlativo' => $correlativo,
                        'tipo_comprobante' => 'Factura',
                    ];
                    
                    // Si la respuesta contiene información de error de Nubefact/Greenter
                    if (isset($response) && is_array($response)) {
                        // Incluir toda la respuesta de Greenter en el log
                        $errorDetails['greenter_response'] = $response;
                        
                        if (isset($response['sunat_description']) && !empty($response['sunat_description'])) {
                            $errorMessage = $response['sunat_description'];
                            $errorDetails['sunat_description'] = $response['sunat_description'];
                        } elseif (isset($response['mensajeUsuario']) && !empty($response['mensajeUsuario'])) {
                            $errorMessage = $response['mensajeUsuario'];
                            $errorDetails['mensaje_usuario'] = $response['mensajeUsuario'];
                        } elseif (isset($response['error']) && !empty($response['error'])) {
                            $errorMessage = $response['error'];
                            $errorDetails['greenter_error'] = $response['error'];
                        }
                        
                        // Agregar códigos y mensajes adicionales si existen
                        if (isset($response['codMensaje'])) {
                            $errorDetails['cod_mensaje'] = $response['codMensaje'];
                        }
                        if (isset($response['exito'])) {
                            $errorDetails['exito'] = $response['exito'];
                        }
                        if (isset($response['sunat_responsecode'])) {
                            $errorDetails['sunat_responsecode'] = $response['sunat_responsecode'];
                        }
                    }
                    
                    // Actualizar factura con error
                    $invoice->update([
                        'envio_estado' => Invoice::ENVIO_FALLIDA,
                        'error_envio' => $errorMessage,
                        'sunat_description' => (isset($response) && is_array($response)) ? ($response['sunat_description'] ?? null) : null,
                        'sunat_responsecode' => (isset($response) && is_array($response)) ? ($response['sunat_responsecode'] ?? null) : null,
                    ]);
                    
                    // Log de error en base de datos
                    $this->logError(
                        'Generar Factura - Error',
                        "Error al generar factura {$serie}-{$correlativo}",
                        $errorDetails,
                        $userId,
                        [
                            'invoice_id' => $invoice->id,
                            'user_package_id' => $userPackageId,
                            'order_id' => $orderId,
                            'client_data' => [
                                'tipoDoc' => $clientData['tipoDoc'] ?? null,
                                'numDoc' => $clientData['numDoc'] ?? null,
                                'rznSocial' => $clientData['rznSocial'] ?? null,
                            ],
                        ]
                    );
                    
                    // Crear excepción con mensaje mejorado
                    throw new \Exception($errorMessage);
                }
            } else {
                // Modo en segundo plano: despachar job
                ProcessSunatInvoice::dispatch($invoice->id);
            }
            
            return [
                'success' => true,
                'message' => $this->isInstantMode() ? 'Factura generada exitosamente' : 'Factura creada, procesándose en segundo plano',
                'data' => [
                    'id' => $invoice->id,
                    'serie' => $serie,
                    'numero' => $correlativo,
                    'numero_completo' => "{$serie}-{$correlativo}",
                    'cliente' => $clientData['rznSocial'] ?? '',
                    'total' => $mtoImpVenta,
                    'fecha' => now()->format('d/m/Y'),
                    'enlace_pdf' => $invoice->enlace_del_pdf,
                    'enlace_xml' => $invoice->enlace_del_xml,
                    'aceptada_por_sunat' => $invoice->aceptada_por_sunat,
                    'envio_estado' => $invoice->envio_estado,
                    'procesado_instantaneo' => $this->isInstantMode(),
                ]
            ];

        } catch (\Throwable $e) {
            // Log de error en base de datos
            $this->logError(
                'Generar Factura - Error General',
                'Error al generar factura',
                [
                    'error_message' => $e->getMessage(),
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile(),
                    'trace' => $e->getTraceAsString(),
                ],
                $userId,
                [
                    'user_package_id' => $userPackageId,
                    'order_id' => $orderId,
                ]
            );
            
            // También log en Laravel
            Log::error('Error al generar factura', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Guardar comprobante en la base de datos
     */
    protected function guardarComprobante(array $data, $response, int $tipoComprobante, ?int $userId, ?int $orderId, array $items, string $envioEstado = Invoice::ENVIO_PENDIENTE, ?int $userPackageId = null): Invoice
    {
        DB::beginTransaction();
        try {
            // Crear el comprobante
            $invoice = Invoice::create([
                'tipo_de_comprobante' => $tipoComprobante,
                'serie' => $data['serie'],
                'numero' => (int) $data['correlativo'],
                'cliente_tipo_de_documento' => (int) $data['client']['tipoDoc'],
                'cliente_numero_de_documento' => $data['client']['numDoc'],
                'cliente_denominacion' => $data['client']['rznSocial'],
                'cliente_direccion' => $data['client']['direccion'] ?? null,
                'cliente_email' => $data['client']['email'] ?? null,
                'fecha_de_emision' => now(),
                'moneda' => 1, // PEN
                'porcentaje_de_igv' => 18.00,
                'total_gravada' => $data['mtoOperGravadas'],
                'total_igv' => $data['mtoIGV'],
                'total' => $data['mtoImpVenta'],
                'user_id' => $userId,
                'order_id' => $orderId,
                'user_package_id' => $userPackageId,
                'envio_estado' => $envioEstado,
                'enviada_a_nubefact' => $envioEstado === Invoice::ENVIO_ENVIADA,
                'enlace_del_pdf' => $response['enlace_del_pdf'] ?? null,
                'enlace_del_xml' => $response['enlace_del_xml'] ?? null,
                'enlace_del_cdr' => $response['enlace_del_cdr'] ?? null,
                'aceptada_por_sunat' => $response['aceptada_por_sunat'] ?? false,
                'sunat_description' => $response['sunat_description'] ?? null,
                'sunat_responsecode' => $response['sunat_responsecode'] ?? null,
                'codigo_hash' => $response['codigo_hash'] ?? null,
            ]);
            
            // Guardar items
            foreach ($items as $index => $item) {
                $cantidad = $item['cantidad'] ?? 1;
                $mtoValorUnitario = $item['mtoValorUnitario'] ?? 0;
                $mtoPrecioUnitario = $item['mtoPrecioUnitario'] ?? ($mtoValorUnitario * 1.18);
                $igvUnitario = $mtoPrecioUnitario - $mtoValorUnitario;
                
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'unidad_de_medida' => $item['unidad'] ?? 'NIU',
                    'codigo' => $item['codProducto'] ?? 'PROD001',
                    'descripcion' => $item['descripcion'] ?? 'Producto',
                    'cantidad' => $cantidad,
                    'valor_unitario' => $mtoValorUnitario,
                    'precio_unitario' => $mtoPrecioUnitario,
                    'subtotal' => $mtoValorUnitario * $cantidad,
                    'tipo_de_igv' => 10,
                    'igv' => $igvUnitario * $cantidad,
                    'total' => $mtoPrecioUnitario * $cantidad,
                ]);
            }
            
            DB::commit();
            return $invoice;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Método de prueba para boletas (mantener compatibilidad)
     */
    public function testBoletaMensual()
    {
        $clientData = [
            'tipoDoc' => '1',
            'numDoc' => '12345678',
            'rznSocial' => 'JUAN PEREZ GARCIA',
        ];
        
        $items = [
            [
                'codProducto' => 'PLAN-MENSUAL',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mtoValorUnitario' => 84.75,
                'descripcion' => 'Plan Mensual Gimnasio Premium - Acceso completo',
                'mtoPrecioUnitario' => 100.00,
            ],
        ];
        
        return $this->generarBoleta($clientData, $items);
    }
}
