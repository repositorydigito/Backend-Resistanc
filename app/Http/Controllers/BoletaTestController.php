<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use CodersFree\LaravelGreenter\Facades\Greenter;

class BoletaTestController extends Controller
{
    public function testBoletaMensual()
    {
        try {
            $data = [
                "ublVersion" => "2.1",
                "tipoOperacion" => "0101",
                "tipoDoc" => "03",
                "serie" => "BBB1",
                "correlativo" => "3",
                "fechaEmision" => now(),
                "formaPago" => ['tipo' => 'Contado'],
                "tipoMoneda" => "PEN",
                "client" => [
                    "tipoDoc" => "1",
                    "numDoc" => "12345678",
                    "rznSocial" => "JUAN PEREZ GARCIA",
                ],
                "mtoOperGravadas" => 84.75,
                "mtoIGV" => 15.25,
                "totalImpuestos" => 15.25,
                "valorVenta" => 84.75,
                "subTotal" => 100.00,
                "mtoImpVenta" => 100.00,
                "details" => [
                    [
                        "codProducto" => "PLAN-MENSUAL",
                        "unidad" => "NIU",
                        "cantidad" => 1,
                        "mtoValorUnitario" => 84.75,
                        "descripcion" => "Plan Mensual Gimnasio Premium - Acceso completo",
                        "mtoBaseIgv" => 84.75,
                        "porcentajeIgv" => 18.00,
                        "igv" => 15.25,
                        "tipAfeIgv" => "10",
                        "totalImpuestos" => 15.25,
                        "mtoValorVenta" => 84.75,
                        "mtoPrecioUnitario" => 100.00,
                    ],
                ],
                "legends" => [
                    [
                        "code" => "1000",
                        "value" => "SON CIEN CON 00/100 SOLES",
                    ],
                ],
            ];

            $response = Greenter::send('invoice', $data);

            return response()->json([
                'success' => true,
                'message' => 'Boleta generada exitosamente',
                'data' => [
                    'serie' => $data['serie'],
                    'numero' => $data['correlativo'],
                    'cliente' => $data['client']['rznSocial'],
                    'plan' => 'Plan Mensual',
                    'total' => $data['mtoImpVenta'],
                    'fecha' => $data['fechaEmision']->format('d/m/Y')
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }
}
