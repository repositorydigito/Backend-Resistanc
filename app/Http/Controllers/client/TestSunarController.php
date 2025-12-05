<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\SunatServices;
use Illuminate\Http\Request;

class TestSunarController extends Controller
{
    protected $sunatService;

    public function __construct(SunatServices $sunatService)
    {
        $this->sunatService = $sunatService;
    }

    /**
     * Ruta de prueba para generar una boleta mensual
     */
    public function testBoletaMensual()
    {
        return $this->sunatService->testBoletaMensual();
    }

    /**
     * Ruta de prueba para generar una factura
     */
    public function testFactura()
    {
        $clientData = [
            'tipoDoc' => '6', // RUC
            'numDoc' => '20123456789',
            'rznSocial' => 'EMPRESA DEMO SAC',
        ];
        
        $items = [
            [
                'codProducto' => 'SERVICIO-001',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mtoValorUnitario' => 84.75,
                'descripcion' => 'Servicio Premium - Acceso completo',
                'mtoPrecioUnitario' => 100.00,
            ],
        ];
        
        return $this->sunatService->generarFactura($clientData, $items);
    }

    /**
     * Ruta de prueba para generar una factura a DIGITO PERU S.A.C.
     */
    public function testFacturaDigitoPeru()
    {
        $clientData = [
            'tipoDoc' => '6', // RUC
            'numDoc' => '20604813418',
            'rznSocial' => 'DIGITO PERU S.A.C.',
            'direccion' => 'AV. VICTOR ANDRES BELAUNDE NRO. 280 DPTO. 701 INT. C URB. EL ROSARIO LIMA - LIMA - SAN ISIDRO',
        ];
        
        $items = [
            [
                'codProducto' => 'SERVICIO-001',
                'unidad' => 'NIU',
                'cantidad' => 1,
                'mtoValorUnitario' => 84.75,
                'descripcion' => 'Servicio Premium - Acceso completo',
                'mtoPrecioUnitario' => 100.00,
            ],
        ];
        
        return $this->sunatService->generarFactura($clientData, $items);
    }
}
