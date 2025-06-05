<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @tags Paquetes
 */
final class PackageController extends Controller
{
    /**
     * Lista todos los paquetes activos del sistema
     *
     * Obtiene una lista paginada de paquetes activos ordenados por precio de menor a mayor.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar paquetes activos
     * @operationId getPackagesList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de paquetes por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "PAQUETE5R",
     *       "slug": "paquete5r",
     *       "description": "Paquete de 5 clases de resistencia para principiantes",
     *       "short_description": "5 clases ideales para comenzar tu rutina",
     *       "classes_quantity": 5,
     *       "price_soles": 120.00,
     *       "original_price_soles": 150.00,
     *       "validity_days": 60,
     *       "package_type": "presencial",
     *       "billing_type": "one_time",
     *       "is_virtual_access": false,
     *       "priority_booking_days": 2,
     *       "auto_renewal": false,
     *       "is_featured": true,
     *       "is_popular": false,
     *       "status": "active",
     *       "display_order": 1,
     *       "features": ["Acceso a todas las máquinas", "Asesoría personalizada"],
     *       "restrictions": ["Válido solo en horarios regulares"],
     *       "target_audience": "beginner",
     *       "membership": {
     *         "id": 1,
     *         "name": "RSISTANC GOLD",
     *         "slug": "rsistanc-gold",
     *         "level": "gold",
     *         "color_hex": "#ebc919",
     *         "benefits": ["Acceso prioritario", "Descuentos especiales"]
     *       },
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $packages = Package::query()
            ->with(['membership'])
            ->withCount(['userPackages'])
            ->active()
            ->orderBy(DB::raw("
            CASE type
                WHEN 'promotion' THEN 1
                WHEN 'offer' THEN 2
                WHEN 'basic' THEN 3
                ELSE 4
            END
        "))
            ->orderBy('display_order', 'asc')
            ->orderBy('price_soles', 'asc')
            ->paginate(
                perPage: $request->integer('per_page', 15),
                page: $request->integer('page', 1)
            );

        return PackageResource::collection($packages);
    }
}
