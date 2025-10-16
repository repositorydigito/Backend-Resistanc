<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * @tags Informacion de la empresa
 */

class CompanyController extends Controller
{

    /**
     * Obtener informacion de la empresa
     *
     */
    public function show()
    {
        try {
            $company = Company::first();

            if (!$company) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Empresa no encontrada',
                    'datoAdicional' => null
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Información de la empresa obtenida correctamente',
                'datoAdicional' => new CompanyResource($company)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener la información de la empresa',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
