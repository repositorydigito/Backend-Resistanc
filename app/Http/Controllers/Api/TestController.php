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
     *
     * Endpoint de prueba para verificar que la API RSISTANC está funcionando correctamente.
     * Retorna información básica del sistema y estadísticas de usuarios.
     *
     * @summary Verificar estado de la API
     * @operationId testApiStatus
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "message": "🚀 API RSISTANC está funcionando correctamente!",
     *   "timestamp": "2024-01-15T10:30:00.000Z",
     *   "users_count": 140,
     *   "version": "1.0.0",
     *   "environment": "local",
     *   "status": "activo"
     * }
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'message' => '🚀 API RSISTANC está funcionando correctamente!',
            'timestamp' => now()->toISOString(),
            'users_count' => User::count(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'status' => 'activo',
        ]);
    }
}
