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
     * Lista todos los paquetes disponibles del sistema
     *
     * Obtiene una lista paginada de paquetes disponibles. Los paquetes fijos se muestran siempre,
     * los paquetes temporales solo se muestran si están dentro del rango de fechas activo.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar paquetes disponibles
     * @operationId getPackagesList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de paquetes por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam discipline_id integer Filtrar por disciplina específica. Example: 1
     * @queryParam mode_type string Filtrar por tipo de modalidad (presencial, virtual, mixto). Example: presencial
     * @queryParam commercial_type string Filtrar por tipo comercial (promotion, offer, basic). Example: promotion
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Cycling - 5 Clases",
     *       "slug": "cycling-5-clases",
     *       "description": "Paquete mensual de cycling con 5 clases",
     *       "short_description": "5 clases de cycling válidas por 1 mes",
     *       "classes_quantity": 5,
     *       "price_soles": 250.00,
     *       "original_price_soles": 250.00,

     *       "mode_type": "presencial",
     *       "billing_type": "one_time",
     *       "type": "fixed",
     *       "commercial_type": "basic",
     *       "start_date": null,
     *       "end_date": null,
     *       "is_virtual_access": false,
     *       "priority_booking_days": 2,
     *       "auto_renewal": false,
     *       "is_featured": false,
     *       "is_popular": true,
     *       "status": "active",
     *       "display_order": 3,
     *       "features": ["5 clases de cycling", "Equipamiento incluido", "Asesoría básica"],
     *       "restrictions": ["Válido por 30 días"],
     *       "target_audience": "intermediate",
     *       "discipline": {
     *         "id": 1,
     *         "name": "CYCLING",
     *         "slug": "cycling",
     *         "description": "Entrenamiento cardiovascular de alta intensidad"
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
            ->with(['discipline', 'membership'])

            ->withCount(['userPackages'])
            ->where('buy_type', 'affordable')

            ->active()
            ->where(function ($query) {
                // Mostrar paquetes fijos siempre
                $query->where('type', 'fixed')
                    // O paquetes temporales que estén en el rango de fechas
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'temporary')
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>=', now());
                    });
            })
            ->when($request->filled('discipline_id'), function ($query) use ($request) {
                $query->where('discipline_id', $request->integer('discipline_id'));
            })
            ->when($request->filled('mode_type'), function ($query) use ($request) {
                $query->where('mode_type', $request->string('mode_type'));
            })
            ->when($request->filled('commercial_type'), function ($query) use ($request) {
                $query->where('commercial_type', $request->string('commercial_type'));
            })
            ->orderByRaw("
            CASE WHEN commercial_type = 'promotion' THEN 0
                 ELSE 1
            END ASC"
        )
            // ->orderBy('display_order', 'asc')
            ->orderBy('price_soles', 'asc')
            ->paginate(
                perPage: $request->integer('per_page', 15),
                page: $request->integer('page', 1)
            );

        return PackageResource::collection($packages);
    }
}
