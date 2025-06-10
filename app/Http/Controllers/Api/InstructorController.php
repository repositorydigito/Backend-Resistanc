<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Instructores
 */
final class InstructorController extends Controller
{
    /**
     * Lista todos los instructores activos del sistema
     *
     * Obtiene una lista paginada de instructores activos con opciones de filtrado y búsqueda.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar instructores activos
     * @operationId getInstructorsList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de instructores por página (máximo 50). Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam search string Buscar por nombre o email del instructor. Example: "Juan"
     * @queryParam status string Filtrar por estado del instructor (active, inactive). Example: "active"
     * @queryParam is_head_coach boolean Filtrar por instructores principales. Example: true
     * @queryParam min_experience_years integer Años mínimos de experiencia. Example: 5
     * @queryParam max_experience_years integer Años máximos de experiencia. Example: 15
     * @queryParam min_rating_average number Calificación mínima promedio. Example: 4.0
     * @queryParam max_rating_average number Calificación máxima promedio. Example: 5.0
     * @queryParam include_counts boolean Incluir contadores de clases y disciplinas. Example: true
     * @queryParam include_disciplines boolean Incluir información de disciplinas. Example: true
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@gmail.com",
     *       "phone": "987654321",
     *       "bio": "Instructor con más de 10 años de experiencia en fitness",
     *       "profile_image": "/images/instructors/juan.jpg",
     *       "instagram_handle": "@juan_fitness",
     *       "is_head_coach": true,
     *       "experience_years": 10,
     *       "rating_average": 4.8,
     *       "total_classes_taught": 500,
     *       "status": "active",
     *       "certifications": ["Spinning Certified", "Pilates Certified"],
     *       "hourly_rate_soles": 150.00,
     *       "hire_date": "2015-01-01",
     *       "disciplines_count": 3,
     *       "classes_count": 25,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/instructors?page=1",
     *     "last": "http://localhost/api/instructors?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/instructors",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Instructor::query()
            ->where('status', 'active')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('is_head_coach'), function ($query) use ($request) {
                $query->where('is_head_coach', $request->boolean('is_head_coach'));
            })
            ->when($request->filled('min_experience_years'), function ($query) use ($request) {
                $query->where('experience_years', '>=', $request->integer('min_experience_years'));
            })
            ->when($request->filled('max_experience_years'), function ($query) use ($request) {
                $query->where('experience_years', '<=', $request->integer('max_experience_years'));
            })
            ->when($request->filled('min_rating_average'), function ($query) use ($request) {
                $query->where('rating_average', '>=', $request->float('min_rating_average'));
            })
            ->when($request->filled('max_rating_average'), function ($query) use ($request) {
                $query->where('rating_average', '<=', $request->float('max_rating_average'));
            });

        // Incluir contadores si se solicita
        if ($request->boolean('include_counts', false)) {
            $query->withCount(['disciplines', 'classSchedules']);
        }

        // Incluir relaciones si se solicita
        if ($request->boolean('include_disciplines', false)) {
            $query->with(['disciplines']);
        }

        // Ordenamiento
        $query->orderBy('is_head_coach', 'desc')
            ->orderBy('rating_average', 'desc')
            ->orderBy('name', 'asc');

        $instructors = $query->paginate(
            perPage: min($request->integer('per_page', 15), 50),
            page: $request->integer('page', 1)
        );

        return InstructorResource::collection($instructors);
    }
}
