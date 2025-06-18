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
     * @queryParam include_counts boolean Incluir contadores de clases y disciplinas. Example: true
     * @queryParam include_disciplines boolean Incluir información de disciplinas. Example: true
     * @queryParam discipline_id integer Filtrar instructores que enseñan una disciplina específica. Example: 3

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
            ->when($request->filled('discipline_id'), function ($query) use ($request) {
                $query->whereHas('disciplines', function ($q) use ($request) {
                    $q->where('id', $request->integer('discipline_id'));
                });
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

    /**
     * Lista los instructores activos del sistema que tienen clases programadas para la semana actual
     *
     * Obtiene una lista de instructores activos que tienen clases programadas para la semana actual.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar instructores activos con clases para la semana actual
     * @operationId getInstructorsWeek
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@gmail.com",
     *       "phone": "987654321",
     *       "profile_image": "/images/instructors/juan.jpg",
     *       "specialties": ["Cycling", "Reforma", "Pilates", "Yoga", "Barre"],
     *       "bio": "Instructor con más de 10 años de experiencia en fitness",
     *       "certifications": ["Spinning Certified", "Pilates Certified"],
     *       "instagram_handle": "@juan_fitness",
     *       "is_head_coach": true,
     *       "experience_years": 10,
     *       "rating_average": 4.8,
     *       "total_classes_taught": 500,
     *       "status": "active",
     *       "availability_schedule": [
     *         {
     *           "day": "monday",
     *           "start_time": "08:00:00",
     *           "end_time": "20:00:00"
     *         },
     *         {
     *           "day": "tuesday",
     *           "start_time": "08:00:00",
     *           "end_time": "20:00:00"
     *         },
     *         {
     *           "day": "wednesday",
     *           "start_time": "08:00:00",
     *           "end_time": "20:00:00"
     *         },
     *         {
     *           "day": "thursday",
     *           "start_time": "08:00:00",
     *           "end_time": "20:00:00"
     *         },
     *         {
     *           "day": "friday",
     *           "start_time": "08:00:00",
     *           "end_time": "20:00:00"
     *         },
     *         {
     *           "day": "saturday",
     *           "start_time": "08:00:00",
     *           "end_time": "14:00:00"
     *         },
     *         {
     *           "day": "sunday",
     *           "start_time": "08:00:00",
     *           "end_time": "14:00:00"
     *         }
     *       ],
     *       "hourly_rate_soles": 150.00,
     *       "hire_date": "2015-01-01",
     *       "class_schedules_count": 5,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ]
     * }
     */
    public function instructorsWeek()
    {
        $instructors = Instructor::query()
            ->active()
            ->withCount(['classSchedules' => function ($query) {
                $query->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()]);
            }])
            ->having('class_schedules_count', '>', 0)
            ->orderBy('class_schedules_count', 'desc')
            ->get();

        return InstructorResource::collection($instructors);
    }

    /**
     * Lista de 10 instructores activos del sistema
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
    public function indexTen(Request $request): AnonymousResourceCollection
    {
        $instructors = Instructor::query()
            ->where('status', 'active')
            ->limit(10)
            ->get();

        return InstructorResource::collection($instructors);
    }
    /**
     * Mostrar detalles de un instructor específico
     *
     * Retorna la información detallada de un instructor, incluyendo disciplinas y horarios de clases.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Mostrar instructor
     * @operationId getInstructorDetails
     *
     * @urlParam instructor integer required ID del instructor. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Juan Pérez",
     *     "email": "juan@gmail.com",
     *     "phone": "987654321",
     *     "bio": "Instructor con más de 10 años de experiencia",
     *     "profile_image": "/images/instructors/juan.jpg",
     *     "instagram_handle": "@juan_fitness",
     *     "is_head_coach": true,
     *     "experience_years": 10,
     *     "rating_average": 4.8,
     *     "certifications": ["Spinning Certified"],
     *     "hourly_rate_soles": 150.00,
     *     "hire_date": "2015-01-01",
     *     "disciplines": [...],
     *     "class_schedules": [...],
     *     "created_at": "2024-01-15T10:30:00.000Z",
     *     "updated_at": "2024-01-15T10:30:00.000Z"
     *   }
     * }
     */

    public function show(Instructor $instructor): InstructorResource
    {
        // Cargar relaciones necesarias
        $instructor->load(['disciplines', 'classSchedules']);

        return new InstructorResource($instructor);
    }

    /**
     * Calificar a un instructor
     *
     * Permite a un usuario autenticado calificar a un instructor. Solo se permite una calificación por instructor por usuario.
     *
     * @summary Calificar instructor
     * @operationId scoreInstructor
     *
     * @urlParam instructor integer required ID del instructor a calificar. Example: 1
     *
     * @bodyParam score integer required Calificación del instructor (1 a 5). Example: 5
     * @bodyParam comment string Comentario opcional. Max 500 caracteres. Example: "Muy buen instructor"
     *
     * @response 200 {
     *   "message": "Calificación registrada exitosamente.",
     *   "rating": {
     *     "id": 10,
     *     "user_id": 2,
     *     "instructor_id": 1,
     *     "score": 5,
     *     "comment": "Muy buen instructor",
     *     "created_at": "2024-06-15T12:34:56.000Z",
     *     "updated_at": "2024-06-15T12:34:56.000Z"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "Ya has calificado a este instructor."
     * }
     */

    public function scoreInstructor(Request $request, Instructor $instructor): \Illuminate\Http\JsonResponse
    {
        // Validar la puntuación

        try {
            // Validar la puntuación
            $request->validate([
                'score' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500',
            ]);

            $userId = auth()->id();

            // Verificar si ya existe una calificación previa
            $alreadyRated = $instructor->ratings()
                ->where('user_id', $userId)
                ->exists();

            if ($alreadyRated) {
                return response()->json([
                    'message' => 'Ya has calificado a este instructor.',
                ], 422); // Código 422 Unprocessable Entity
            }

            // Crear la calificación
            $rating = $instructor->ratings()->create([
                'user_id' => $userId,
                'score' => $request->input('score'),
                'comment' => $request->input('comment'),
            ]);

            // Actualizar el promedio de calificaciones del instructor
            $instructor->updateRatingAverage();

            return response()->json([
                'message' => 'Calificación registrada exitosamente.',
                'rating' => $rating,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al registrar la calificación: ' . $th->getMessage(),
            ], 500); // Código 500 Internal Server Error
        }
    }
}
