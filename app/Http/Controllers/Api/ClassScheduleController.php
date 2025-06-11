<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Horarios de Clases
 */
final class ClassScheduleController extends Controller
{
    /**
     * Lista todos los horarios de clases programados
     *
     * Obtiene una lista de horarios de clases futuras con opciones de filtrado por clase, instructor, estudio y disciplina.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Listar horarios de clases programados
     * @operationId getClassSchedulesList
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     *
     * @queryParam per_page integer Número de horarios por página (máximo 50). Si no se especifica, devuelve todos los resultados. Example: 15
     * @queryParam page integer Número de página para la paginación. Example: 1
     * @queryParam class_id integer Filtrar por ID de clase específica. Example: 1
     * @queryParam instructor_id integer Filtrar por ID de instructor específico. Example: 2
     * @queryParam studio_id integer Filtrar por ID de estudio específico. Example: 3
     * @queryParam discipline_id integer Filtrar por ID de disciplina específica. Example: 1
     * @queryParam scheduled_date string Filtrar por fecha específica (YYYY-MM-DD). Example: "2024-06-15"
     * @queryParam date_from string Filtrar desde una fecha específica (YYYY-MM-DD). Example: "2024-06-10"
     * @queryParam date_to string Filtrar hasta una fecha específica (YYYY-MM-DD). Example: "2024-06-20"
     * @queryParam search string Buscar por nombre de clase. Example: "Yoga"
     * @queryParam include_counts boolean Incluir contadores de reservas y asientos. Example: true
     * @queryParam include_relations boolean Incluir información completa de relaciones. Example: true
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "scheduled_date": "2024-06-15",
     *       "start_time": "08:00:00",
     *       "end_time": "09:00:00",
     *       "status": "scheduled",
     *       "max_capacity": 20,
     *       "current_reservations": 15,
     *       "available_spots": 5,
     *       "notes": "Traer mat de yoga",
     *       "class": {
     *         "id": 1,
     *         "name": "Hatha Yoga",
     *         "duration_minutes": 60,
     *         "difficulty_level": "beginner"
     *       },
     *       "instructor": {
     *         "id": 2,
     *         "name": "Ana López",
     *         "profile_image": "/images/instructors/ana.jpg"
     *       },
     *       "studio": {
     *         "id": 3,
     *         "name": "Sala Yoga A",
     *         "max_capacity": 20
     *       },
     *       "seats_count": 15,
     *       "reservations_count": 15,
     *       "created_at": "2024-01-15T10:30:00.000Z",
     *       "updated_at": "2024-01-15T10:30:00.000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost/api/class-schedules?page=1",
     *     "last": "http://localhost/api/class-schedules?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost/api/class-schedules",
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ClassSchedule::query()
            ->where('scheduled_date', '>=', now())
            ->where('status', 'scheduled')
            ->with(['class', 'instructor', 'studio']);

        // Filtros opcionales
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->integer('instructor_id'));
        }

        if ($request->filled('studio_id')) {
            $query->where('studio_id', $request->integer('studio_id'));
        }

        if ($request->filled('discipline_id')) {
            $query->whereHas('class', function ($query) use ($request) {
                $query->where('discipline_id', $request->integer('discipline_id'));
            });
        }

        // Filtros de fecha
        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->date('scheduled_date'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->date('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('class', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            });
        }

        // Incluir contadores si se solicita
        if ($request->boolean('include_counts', false)) {
            $query->withCount(['seats', 'reservations']);
        }

        // Incluir relaciones adicionales si se solicita
        if ($request->boolean('include_relations', false)) {
            $query->with(['class.discipline', 'seats.user']);
        }

        // Ordenamiento por fecha y hora
        $query->orderBy('scheduled_date', 'asc')
            ->orderBy('start_time', 'asc');

        // Si no se especifica per_page, devolver todo sin paginar
        if ($request->has('per_page')) {
            $schedules = $query->paginate(
                perPage: min($request->integer('per_page', 15), 50),
                page: $request->integer('page', 1)
            );
        } else {
            $schedules = $query->get();
        }

        return ClassScheduleResource::collection($schedules);
    }


    /**
     * Obtiene un horario de clase específico
     *
     * Obtiene los detalles de un horario de clase específico, incluyendo información de la clase, instructor y estudio.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Obtener horario de clase específico
     * @operationId getClassSchedule
     *
     * @param  int  $id
     * @return \App\Http\Resources\ClassScheduleResource
     *
     * @response 200 {
     *   "id": 1,
     *   "class": {
     *     "id": 1,
     *     "name": "Hatha Yoga",
     *     "discipline": "Yoga"
     *   },
     *   "instructor": {
     *     "id": 2,
     *     "name": "Ana López"
     *   },
     *   "studio": {
     *     "id": 3,
     *     "name": "Sala Yoga A"
     *   },
     *   "scheduled_date": "15/06/2024",
     *   "start_time": "08:00:00",
     *   "end_time": "09:00:00",
     *   "max_capacity": 20,
     *   "available_spots": 5,
     *   "booked_spots": 15,
     *   "waitlist_spots": 0,
     *   "booking_opens_at": "14/06/2024 08:00:00",
     *   "booking_closes_at": "15/06/2024 07:00:00",
     *   "cancellation_deadline": "14/06/2024 12:00:00",
     *   "special_notes": "Traer mat de yoga",
     *   "is_holiday_schedule": false,
     *   "status": "scheduled"
     * }
     */

    public function show(int $id)
    {
        $schedule = ClassSchedule::with(['class', 'instructor', 'studio'])
            ->findOrFail($id);

        return new ClassScheduleResource($schedule);
    }
}
