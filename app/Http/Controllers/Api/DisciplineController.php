<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplineResource;
use App\Models\Discipline;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Disciplinas
 */
final class DisciplineController extends Controller
{
    /**
     * Lista todas las disciplinas activas del sistema
     *
     * Obtiene una lista paginada de disciplinas activas ordenadas por orden de visualización.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar disciplinas activas
     * @operationId getDisciplinesList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de disciplinas por página (máximo 100). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam include_counts boolean Incluir contadores de clases e instructores. Example: true
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "yoga",
     *       "display_name": "Yoga",
     *       "description": "Práctica física, mental y espiritual que combina posturas, respiración y meditación",
     *       "icon_url": "https://example.com/icons/yoga.svg",
     *       "color_hex": "#7C3AED",
     *       "equipment_required": ["Mat de yoga", "Bloques"],
     *       "difficulty_level": "beginner",
     *       "calories_per_hour_avg": 180,
     *       "is_active": true,
     *       "sort_order": 1,
     *       "classes_count": 25,
     *       "instructors_count": 8,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/disciplines?page=1",
     *     "last": "http://localhost/api/disciplines?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/disciplines",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Discipline::query()
            ->active()
            ->whereHas('packages')
            ->orderBy('sort_order', 'asc')
            ->orderBy('display_name', 'asc');

        // Incluir contadores si se solicita
        if ($request->boolean('include_counts', false)) {
            $query->withCount(['classes', 'instructors']);
        }

        // Si no se especifica per_page, devolver todo sin paginar
        if ($request->has('per_page')) {
            $disciplines = $query->paginate(
                perPage: $request->integer('per_page', 15),
                page: $request->integer('page', 1)
            );
        } else {
            $disciplines = $query->get();
        }

        return DisciplineResource::collection($disciplines);
    }
}
