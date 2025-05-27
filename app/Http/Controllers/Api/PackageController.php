<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;


/**
 * @tags Paquetes
 */
final class PackageController extends Controller
{
    /**
     * Lista todos los paquetes activos del sistema
     *
     * Obtiene una lista paginada de paquetes activos ordenados por precio de menor a mayor.
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
     *       "features": ["Acceso a todas las máquinas", "Asesoría personalizada", "Plan nutricional básico"],
     *       "restrictions": ["Válido solo en horarios regulares", "No incluye días festivos"],
     *       "target_audience": "beginner",
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z",
     *       "is_unlimited": false,
     *       "is_on_sale": true,
     *       "discount_percentage": 20,
     *       "features_string": "Acceso a todas las máquinas, Asesoría personalizada, Plan nutricional básico",
     *       "restrictions_string": "Válido solo en horarios regulares, No incluye días festivos",
     *       "price_per_credit": 24.00,
     *       "type_display_name": "Presencial",
     *       "billing_type_display_name": "Pago Único",
     *       "validity_period": "2 meses",
     *       "is_active": true,
     *       "user_packages_count": 45,
     *       "active_user_packages_count": 12
     *     }
     *   ],
     *   "links": {
     *     "first": "http://rsistanc.test/api/packages?page=1",
     *     "last": "http://rsistanc.test/api/packages?page=5",
     *     "prev": null,
     *     "next": "http://rsistanc.test/api/packages?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 5,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 67
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $packages = Package::query()
            ->withCount(['userPackages'])
            ->active() // Solo paquetes activos
            ->orderBy('price_soles', 'asc') // Ordenar por precio de menor a mayor
            ->paginate($request->integer('per_page', 15));

        return PackageResource::collection($packages);
    }
}
