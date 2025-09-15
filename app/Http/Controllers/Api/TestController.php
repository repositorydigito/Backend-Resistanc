<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @tags Sistema
 */
final class TestController extends Controller
{
    /**
     * Verifica el estado de la API
     */
    public function status(): JsonResponse
    {

        try {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error en el sistema',
                'datoAdicional' => [
                    'message' => '🚀 API RSISTANC está funcionando correctamente!',
                    'timestamp' => now()->toISOString(),
                    'users_count' => User::count(),
                    'version' => config('app.version', '1.0.0'),
                    'environment' => app()->environment(),
                    'status' => 'activo',
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error en el sistema',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
