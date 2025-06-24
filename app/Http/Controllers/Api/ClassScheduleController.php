<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassScheduleIndexRequest;
use App\Http\Requests\ReserveSeatsRequest;
use App\Http\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleSeat;
use App\Models\WaitingClass;
use App\Services\PackageValidationService;
use Error;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     * @tags Horarios de Clases
     *
     * @param  \App\Http\Requests\ClassScheduleIndexRequest  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
    public function index(ClassScheduleIndexRequest $request): AnonymousResourceCollection
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
     * Obtiene los detalles de un horario de clase específico, incluyendo información de la clase, instructor, estudio y asientos disponibles.
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     * @summary Obtener horario de clase específico
     * @operationId getClassSchedule
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\ClassScheduleResource
     *
     * @queryParam include_seats boolean Incluir información detallada de asientos. Example: true
     * @queryParam include_available_seats boolean Incluir solo asientos disponibles. Example: true
     * @queryParam include_reserved_seats boolean Incluir solo asientos reservados. Example: true
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
     *   "status": "scheduled",
     *   "seats": {
     *     "available": [
     *       {
     *         "id": 1,
     *         "seat_number": "A1",
     *         "row": "A",
     *         "column": 1,
     *         "status": "available"
     *       }
     *     ],
     *     "reserved": [
     *       {
     *         "id": 2,
     *         "seat_number": "A2",
     *         "row": "A",
     *         "column": 2,
     *         "status": "reserved",
     *         "user": {
     *           "id": 10,
     *           "name": "Juan Pérez"
     *         },
     *         "reserved_at": "2024-06-14T10:30:00.000Z",
     *         "expires_at": "2024-06-15T07:00:00.000Z"
     *       }
     *     ],
     *     "occupied": [],
     *     "blocked": []
     *   },
     *   "seats_summary": {
     *     "total_seats": 20,
     *     "available_count": 5,
     *     "reserved_count": 15,
     *     "occupied_count": 0,
     *     "blocked_count": 0
     *   }
     * }
     */
    public function show(int $id, Request $request)
    {
        $query = ClassSchedule::with(['class', 'instructor', 'studio']);

        // Incluir información de asientos si se solicita
        if ($request->boolean('include_seats', false)) {
            $query->with(['seats' => function ($query) {
                $query->withPivot(['user_id', 'status', 'reserved_at', 'expires_at']);
            }]);
        }

        // Incluir solo asientos disponibles
        if ($request->boolean('include_available_seats', false)) {
            $query->with(['seats' => function ($query) {
                $query->wherePivot('status', 'available')
                    ->withPivot(['user_id', 'status', 'reserved_at', 'expires_at']);
            }]);
        }

        // Incluir solo asientos reservados
        if ($request->boolean('include_reserved_seats', false)) {
            $query->with(['seats' => function ($query) {
                $query->wherePivotIn('status', ['reserved', 'occupied'])
                    ->with('user:id,name,email')
                    ->withPivot(['user_id', 'status', 'reserved_at', 'expires_at']);
            }]);
        }

        // Siempre incluir contadores de asientos
        $query->withCount([
            'seats as total_seats_count',
            'seats as available_seats_count' => function ($query) {
                $query->where('class_schedule_seat.status', 'available');
            },
            'seats as reserved_seats_count' => function ($query) {
                $query->where('class_schedule_seat.status', 'reserved');
            },
            'seats as occupied_seats_count' => function ($query) {
                $query->where('class_schedule_seat.status', 'occupied');
            },
            'seats as blocked_seats_count' => function ($query) {
                $query->where('class_schedule_seat.status', 'blocked');
            }
        ]);

        $schedule = $query->findOrFail($id);

        return new ClassScheduleResource($schedule);
    }


    /**
     * Obtiene el mapa de asientos de un horario de clase
     *
     * Devuelve la disposición visual de todos los asientos del estudio para el horario específico,
     * incluyendo su estado actual (disponible, reservado, ocupado, bloqueado).
     *
     * @summary Obtener mapa de asientos
     * @operationId getClassScheduleSeatMap
     *
     * @param  \App\Models\ClassSchedule  $classSchedule
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "studio": {
     *       "id": 1,
     *       "name": "Cycling Studio A",
     *       "max_capacity": 20,
     *       "rows": 4,
     *       "columns": 5
     *     },
     *     "seat_map": [
     *       [
     *         {
     *           "id": 1,
     *           "seat_number": "A1",
     *           "row": "A",
     *           "column": 1,
     *           "status": "available",
     *           "user": null
     *         },
     *         {
     *           "id": 2,
     *           "seat_number": "A2",
     *           "row": "A",
     *           "column": 2,
     *           "status": "reserved",
     *           "user": {
     *             "id": 10,
     *             "name": "Juan Pérez"
     *           }
     *         }
     *       ]
     *     ],
     *     "summary": {
     *       "total_seats": 20,
     *       "available": 15,
     *       "reserved": 4,
     *       "occupied": 1,
     *       "blocked": 0
     *     }
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Horario de clase no encontrado"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "No hay asientos configurados para este horario",
     *   "data": null
     * }
     */
    /**
     * Obtiene el mapa de asientos de un horario de clase
     *
     * Devuelve la disposición visual de todos los asientos del estudio para el horario específico,
     * incluyendo su estado actual (disponible, reservado, ocupado, bloqueado).
     *
     * @summary Obtener mapa de asientos
     * @operationId getClassScheduleSeatMap
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "studio": {
     *       "id": 1,
     *       "name": "Cycling Studio A",
     *       "max_capacity": 20,
     *       "rows": 4,
     *       "columns": 5
     *     },
     *     "seat_map": [
     *       [
     *         {
     *           "id": 1,
     *           "seat_number": "A1",
     *           "row": "A",
     *           "column": 1,
     *           "status": "available",
     *           "user": null
     *         },
     *         {
     *           "id": 2,
     *           "seat_number": "A2",
     *           "row": "A",
     *           "column": 2,
     *           "status": "reserved",
     *           "user": {
     *             "id": 10,
     *             "name": "Juan Pérez"
     *           }
     *         }
     *       ]
     *     ],
     *     "summary": {
     *       "total_seats": 20,
     *       "available": 15,
     *       "reserved": 4,
     *       "occupied": 1,
     *       "blocked": 0
     *     }
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Horario de clase no encontrado"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "No hay asientos configurados para este horario",
     *   "data": null
     * }
     */
    public function getSeatMap(ClassSchedule $classSchedule)
    {
        try {

            // Verificar que el horario está activo
            if ($classSchedule->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Horario de clase no disponible',
                    'data' => null
                ], 422);
            }

            // Cargar las relaciones necesarias
            $classSchedule->load(['studio', 'seats']);

            // Verificar que el horario tiene un estudio asignado
            if (!$classSchedule->studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay estudio asignado a este horario',
                    'data' => null
                ], 422);
            }

            // Intentar obtener el mapa de asientos
            $seatMap = $classSchedule->getSeatMap();

            // Verificar que el método devolvió datos válidos
            if (!$seatMap || (is_array($seatMap) && empty($seatMap))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay asientos configurados para este horario',
                    'data' => null
                ], 422);
            }

            // Si getSeatMap() devuelve un array simple, estructurarlo mejor
            if (is_array($seatMap) && !isset($seatMap['studio'])) {
                $seatMap = [
                    'studio' => [
                        'id' => $classSchedule->studio->id,
                        'name' => $classSchedule->studio->name,
                        'max_capacity' => $classSchedule->studio->max_capacity,
                        'rows' => $classSchedule->studio->row,
                        'columns' => $classSchedule->studio->column,
                        'location' => $classSchedule->studio->location
                    ],
                    'class' => [
                        'id' => $classSchedule->class->id,
                        'name' => $classSchedule->class->name,
                        'discipline' => $classSchedule->class->discipline->name,
                        'discipline_img' => asset('storage/') . '/' . $classSchedule->class->discipline->icon_url,
                    ],
                    'instructor' => [
                        'id' => $classSchedule->instructor->id,
                        'name' => $classSchedule->instructor->name,
                        'profile_image' =>  asset('storage/') . '/' . $classSchedule->instructor->profile_image,
                    ],
                    'seat_map' => $seatMap,
                    'summary' => $this->calculateSeatSummary($seatMap)
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Mapa de asientos obtenido exitosamente',
                'data' => $seatMap
            ], 200);
        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error al obtener mapa de asientos', [
                'class_schedule_id' => $classSchedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener el mapa de asientos',
                'data' => null
            ], 500);
        }
    }

    /**
     * Calcula el resumen de estados de asientos
     *
     * @param array $seatMap
     * @return array
     */
    private function calculateSeatSummary(array $seatMap): array
    {
        $summary = [
            'total_seats' => 0,
            'available' => 0,
            'reserved' => 0,
            'occupied' => 0,
            'blocked' => 0
        ];

        // Si es un array multidimensional (filas)
        if (is_array($seatMap) && isset($seatMap[0]) && is_array($seatMap[0])) {
            foreach ($seatMap as $row) {
                foreach ($row as $seat) {
                    if (isset($seat['status'])) {
                        $summary['total_seats']++;
                        $status = $seat['status'];
                        if (isset($summary[$status])) {
                            $summary[$status]++;
                        }
                    }
                }
            }
        }
        // Si es un array simple de asientos
        else {
            foreach ($seatMap as $seat) {
                if (isset($seat['status'])) {
                    $summary['total_seats']++;
                    $status = $seat['status'];
                    if (isset($summary[$status])) {
                        $summary[$status]++;
                    }
                }
            }
        }

        return $summary;
    }

    /**
     * Reservar múltiples asientos para el usuario autenticado
     *
     * Permite al usuario autenticado reservar uno o más asientos en un horario de clase específico.
     * Los asientos se reservan temporalmente y expiran después del tiempo especificado.
     *
     * **IMPORTANTE:** El usuario debe tener paquetes disponibles para la disciplina de la clase.
     * El sistema validará automáticamente que el usuario tenga paquetes activos y con clases
     * disponibles para la disciplina específica antes de permitir la reserva.
     *
     * @summary Reservar asientos en horario
     * @operationId reserveSeatsInSchedule
     * @tags Reservas de Asientos
     *
     * @param  \App\Models\ClassSchedule  $classSchedule Horario de clase
     * @param  \App\Http\Requests\ReserveSeatsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Asientos reservados exitosamente",
     *   "data": {
     *     "reserved_seats": [
     *       {
     *         "class_schedule_seat_id": 267,
     *         "seat_id": 1,
     *         "seat_number": "1.1",
     *         "row": 1,
     *         "column": 1,
     *         "status": "reserved",
     *         "reserved_at": "2024-06-15T10:30:00.000Z",
     *         "expires_at": "2024-06-15T10:45:00.000Z"
     *       }
     *     ],
     *     "reservation_summary": {
     *       "total_reserved": 2,
     *       "expires_in_minutes": 15,
     *       "user_id": 10,
     *       "schedule_id": 5,
     *       "class_name": "Yoga Matutino",
     *       "studio_name": "Sala Principal",
     *       "scheduled_date": "2025-01-15",
     *       "start_time": "08:00:00"
     *     },
     *     "package_consumption": {
     *       "id": 42,
     *       "package_code": "PKG001-2024",
     *       "package_name": "Paquete Yoga 10 Clases",
     *       "classes_consumed": 1,
     *       "remaining_classes": 9,
     *       "used_classes": 1
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Algunos asientos no están disponibles",
     *   "data": {
     *     "unavailable_seats": [1, 3],
     *     "available_seats": [2, 4]
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Horario de clase no encontrado"
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "No tienes paquetes disponibles para la disciplina 'Yoga'",
     *   "data": {
     *     "reason": "insufficient_packages",
     *     "discipline_required": {
     *       "id": 1,
     *       "name": "Yoga"
     *     },
     *     "available_packages": []
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "No se puede reservar en este horario",
     *   "data": {
     *     "reason": "booking_closed"
     *   }
     * }
     */
    public function reserveSeats(ClassSchedule $classSchedule, ReserveSeatsRequest $request)
    {


        try {
            // Log inicial para debugging

            Log::info('Iniciando reserva de asientos', [
                'schedule_id' => $classSchedule->id,
                'request_data' => $request->validated(),
                'user_id' => Auth::id()
            ]);

            // Verificar que el horario permite reservas
            if ($classSchedule->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reservar en un horario cancelado',
                    'data' => ['reason' => 'schedule_cancelled']
                ], 200);
            }

            // Verificar que no sea un horario pasado
            $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                ? $classSchedule->scheduled_date->format('Y-m-d')
                : $classSchedule->scheduled_date;

            $scheduleDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $classSchedule->start_time);
            if ($scheduleDateTime->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede reservar en un horario pasado',
                    'data' => ['reason' => 'schedule_past']
                ], 200);
            }

            // Verificar que las reservas estén abiertas (al menos 2 horas antes)
            $hoursUntilClass = now()->diffInHours($scheduleDateTime, false);
            if ($hoursUntilClass < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las reservas se cierran 2 horas antes del inicio de la clase',
                    'data' => [
                        'reason' => 'booking_closed',
                        'hours_until_class' => $hoursUntilClass,
                        'class_datetime' => $scheduleDateTime->toISOString(),
                        'current_time' => now()->toISOString()
                    ]
                ], 200);
            }

            $userId = Auth::id();
            $classScheduleSeatIds = $request->validated()['class_schedule_seat_ids'];
            $minutesToExpire = $request->validated()['minutes_to_expire'];

            // 🎯 VALIDAR PAQUETES DISPONIBLES PARA LA DISCIPLINA
            $packageValidationService = new PackageValidationService();
            $packageValidation = $packageValidationService->validateUserPackagesForSchedule($classSchedule, $userId);

            if (!$packageValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $packageValidation['message'],
                    'data' => [
                        'reason' => 'insufficient_packages',
                        'discipline_required' => $packageValidation['discipline_required'],
                        'available_packages' => $packageValidation['available_packages']
                    ]
                ], 200);
            }

            // Log de paquetes disponibles para debugging
            Log::info('Paquetes validados para reserva', [
                'user_id' => $userId,
                'schedule_id' => $classSchedule->id,
                'discipline_required' => $packageValidation['discipline_required'],
                'available_packages_count' => count($packageValidation['available_packages'])
            ]);

            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($classSchedule, $classScheduleSeatIds, $userId, $minutesToExpire, $packageValidationService, $packageValidation) {
                // Obtener los paquetes disponibles como modelos Eloquent
                $classSchedule->load(['class.discipline']);
                $disciplineId = $classSchedule->class->discipline_id;
                $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline($userId, $disciplineId);
                $totalAvailableSeats = $availablePackages->sum('remaining_classes');

                // Validar que el usuario no reserve más asientos de los que tiene disponibles
                if (count($classScheduleSeatIds) > $totalAvailableSeats) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes suficientes asientos disponibles en tus paquetes para reservar esta cantidad.',
                        'data' => [
                            'requested_seats' => count($classScheduleSeatIds),
                            'available_seats' => $totalAvailableSeats
                        ]
                    ], 200);
                }

                // Verificar que todos los asientos existen y pertenecen a este horario
                $seatAssignments = ClassScheduleSeat::where('class_schedules_id', $classSchedule->id)
                    ->whereIn('id', $classScheduleSeatIds)
                    ->lockForUpdate() // Bloquear para evitar condiciones de carrera
                    ->get();

                // Verificar que todos los asientos solicitados existen en este horario
                $foundAssignmentIds = $seatAssignments->pluck('id')->toArray();
                $missingAssignmentIds = array_diff($classScheduleSeatIds, $foundAssignmentIds);

                if (!empty($missingAssignmentIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Algunos asientos no están asignados a este horario',
                        'data' => [
                            'missing_assignment_ids' => $missingAssignmentIds,
                            'available_assignment_ids' => $foundAssignmentIds
                        ]
                    ], 400);
                }

                // Verificar disponibilidad de cada asiento
                $unavailableSeats = [];
                $availableAssignments = [];

                foreach ($seatAssignments as $assignment) {
                    if ($assignment->status !== 'available') {
                        $unavailableSeats[] = [
                            'class_schedule_seat_id' => $assignment->id,
                            'seat_id' => $assignment->seats_id,
                            'current_status' => $assignment->status,
                            'user_id' => $assignment->user_id
                        ];
                    } else {
                        $availableAssignments[] = $assignment;
                    }
                }

                // Si hay asientos no disponibles, devolver error
                if (!empty($unavailableSeats)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Algunos asientos no están disponibles',
                        'data' => [
                            'unavailable_seats' => $unavailableSeats,
                            'available_seats' => collect($availableAssignments)->pluck('id')->toArray()
                        ]
                    ], 400);
                }

                // Reservar todos los asientos disponibles
                $reservedSeats = [];
                $reservedAt = now();
                $expiresAt = $reservedAt->copy()->addMinutes($minutesToExpire);

                // Consumir un asiento de un paquete por cada asiento reservado
                $availablePackages = $availablePackages->sortBy('expiry_date')->values();
                $packageIndex = 0;
                foreach ($availableAssignments as $i => $assignment) {
                    // Buscar el siguiente paquete con clases disponibles
                    while ($packageIndex < $availablePackages->count() && $availablePackages[$packageIndex]->remaining_classes <= 0) {
                        $packageIndex++;
                    }
                    if ($packageIndex >= $availablePackages->count()) {
                        // Esto no debería ocurrir por la validación previa
                        break;
                    }
                    $package = $availablePackages[$packageIndex];
                    $package->loadMissing('package'); // Asegura que la relación esté cargada
                    $package->useClasses(1);
                    $packageConsumptionDetails[] = [
                        'package_id' => $package->id,
                        'package_code' => $package->package_code,
                        'package_name' => $package->package->name ?? 'N/A',
                        'remaining_classes' => $package->remaining_classes
                    ];

                    // Actualizar el asiento con el user_package_id
                    $assignment->update([
                        'user_id' => $userId,
                        'status' => 'reserved',
                        'reserved_at' => $reservedAt,
                        'expires_at' => $expiresAt,
                        'user_package_id' => $package->id
                    ]);

                    // Cargar la relación del asiento para obtener información completa
                    $assignment->load('seat');

                    $reservedSeats[] = [
                        'class_schedule_seat_id' => $assignment->id,
                        'seat_id' => $assignment->seats_id,
                        'seat_number' => $assignment->seat->row . '.' . $assignment->seat->column,
                        'row' => $assignment->seat->row,
                        'column' => $assignment->seat->column,
                        'status' => 'reserved',
                        'user_id' => $userId,
                        'reserved_at' => $reservedAt->toISOString(),
                        'expires_at' => $expiresAt->toISOString(),
                        'assignment_id' => $assignment->id,
                        'schedule_id' => $classSchedule->id,
                        'user_package_id' => $package->id, // Mostrar el paquete usado
                        'package_code' => $package->package_code,
                        'package_name' => $package->package->name ?? 'N/A'
                    ];
                }

                // Preparar respuesta exitosa
                return response()->json([
                    'success' => true,
                    'message' => 'Asientos reservados exitosamente',
                    'data' => [
                        'reserved_seats' => $reservedSeats,
                        'reservation_summary' => [
                            'total_reserved' => count($reservedSeats),
                            'expires_in_minutes' => $minutesToExpire,
                            'expires_at' => $expiresAt->toISOString(),
                            'user_id' => $userId,
                            'schedule_id' => $classSchedule->id,
                            'class_name' => $classSchedule->class->name ?? 'N/A',
                            'studio_name' => $classSchedule->studio->name ?? 'N/A',
                            'scheduled_date' => $classSchedule->scheduled_date,
                            'start_time' => $classSchedule->start_time
                        ],
                        'package_consumption' => $packageConsumptionDetails
                    ]
                ], 200);
            });
        } catch (Error $e) {
            // Log del error para debugging
            Log::error('Error al reservar asientos', [
                'class_schedule_id' => $classSchedule->id,
                'user_id' => Auth::id(),
                'class_schedule_seat_ids' => $request->validated()['class_schedule_seat_ids'] ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al reservar asientos',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liberar/cancelar reserva de asientos usando el ID de class_schedule_seat
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam class_schedule_seat_ids array required Los IDs de la tabla class_schedule_seat a liberar. Example: [267, 268, 269]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Reservas liberadas exitosamente",
     *   "data": {
     *     "released_seats": [
     *       {
     *         "class_schedule_seat_id": 267,
     *         "seat_id": 1,
     *         "seat_number": "1.1",
     *         "previous_status": "reserved",
     *         "new_status": "available",
     *         "released_at": "2025-01-11T20:30:00.000000Z"
     *       }
     *     ],
     *     "release_summary": {
     *       "total_released": 3,
     *       "user_id": 10
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Algunos asientos no pueden ser liberados",
     *   "data": {
     *     "invalid_assignments": [...]
     *   }
     * }
     */
    public function releaseSeats(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_seat_ids' => 'required|array|min:1|max:10',
                'class_schedule_seat_ids.*' => 'required|integer|exists:class_schedule_seat,id'
            ]);

            $userId = Auth::id();
            $assignmentIds = $request->validated()['class_schedule_seat_ids'];

            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($assignmentIds, $userId) {

                // Obtener las asignaciones que pertenecen al usuario autenticado
                $assignments = ClassScheduleSeat::whereIn('id', $assignmentIds)
                    ->where('user_id', $userId) // Solo puede liberar sus propias reservas
                    ->lockForUpdate()
                    ->get();

                // Verificar que todas las asignaciones existen y pertenecen al usuario
                $foundIds = $assignments->pluck('id')->toArray();
                $missingIds = array_diff($assignmentIds, $foundIds);

                if (!empty($missingIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Algunos asientos no pueden ser liberados (no existen o no te pertenecen)',
                        'data' => [
                            'invalid_assignment_ids' => $missingIds,
                            'valid_assignment_ids' => $foundIds
                        ]
                    ], 400);
                }

                // Liberar todos los asientos
                $releasedSeats = [];
                $releasedAt = now();

                foreach ($assignments as $assignment) {
                    $previousStatus = $assignment->status;

                    // Cargar la relación del asiento
                    $assignment->load('seat');

                    // Actualizar a disponible
                    $assignment->update([
                        'user_id' => null,
                        'status' => 'available',
                        'reserved_at' => null,
                        'expires_at' => null
                    ]);

                    $releasedSeats[] = [
                        'class_schedule_seat_id' => $assignment->id,
                        'seat_id' => $assignment->seats_id,
                        'seat_number' => $assignment->seat->row . '.' . $assignment->seat->column,
                        'row' => $assignment->seat->row,
                        'column' => $assignment->seat->column,
                        'previous_status' => $previousStatus,
                        'new_status' => 'available',
                        'released_at' => $releasedAt->toISOString()
                    ];
                }

                // Preparar respuesta exitosa
                return response()->json([
                    'success' => true,
                    'message' => 'Reservas liberadas exitosamente',
                    'data' => [
                        'released_seats' => $releasedSeats,
                        'release_summary' => [
                            'total_released' => count($releasedSeats),
                            'user_id' => $userId,
                            'released_at' => $releasedAt->toISOString()
                        ]
                    ]
                ], 200);
            });
        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error al liberar asientos', [
                'user_id' => Auth::id(),
                'assignment_ids' => $request->input('class_schedule_seat_ids', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al liberar asientos',
                'data' => null
            ], 500);
        }
    }

    /**
     * Obtener las reservas de asientos del usuario autenticado
     *
     * Muestra todos los horarios donde el usuario tiene asientos reservados,
     * incluyendo información completa de la clase, estudio, asientos y estado de las reservas.
     *
     * @summary Mis reservas de asientos
     * @operationId getMyReservations
     * @tags Mis Reservas
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @queryParam status string Filtrar por estado de reserva (reserved, occupied, completed). Example: reserved
     * @queryParam date_from string Filtrar desde fecha (Y-m-d). Example: 2025-01-15
     * @queryParam date_to string Filtrar hasta fecha (Y-m-d). Example: 2025-01-30
     * @queryParam upcoming boolean Solo reservas futuras (true/false). Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Reservas obtenidas exitosamente",
     *   "data": {
     *     "reservations": [
     *       {
     *         "schedule_id": 13,
     *         "class_name": "Yoga Matutino",
     *         "instructor_name": "María García",
     *         "studio_name": "Sala Principal",
     *         "scheduled_date": "2025-01-15",
     *         "start_time": "08:00:00",
     *         "end_time": "09:00:00",
     *         "class_status": "scheduled",
     *         "my_seats": [
     *           {
     *             "class_schedule_seat_id": 266,
     *             "seat_id": 1,
     *             "seat_number": "1.1",
     *             "row": 1,
     *             "column": 1,
     *             "status": "reserved",
     *             "reserved_at": "2025-01-11T20:30:00.000000Z",
     *             "expires_at": "2025-01-11T20:45:00.000000Z"
     *           }
     *         ],
     *         "total_my_seats": 2,
     *         "can_cancel": true,
     *         "cancellation_deadline": "2025-01-15T07:00:00.000000Z"
     *       }
     *     ],
     *     "summary": {
     *       "total_reservations": 5,
     *       "upcoming_reservations": 3,
     *       "past_reservations": 2,
     *       "total_seats_reserved": 8
     *     }
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "No tienes reservas de asientos"
     * }
     */
    public function getMyReservations(Request $request)
    {
        try {
            $userId = Auth::id();

            // Validar parámetros de filtro
            $request->validate([
                'status' => 'nullable|string|in:reserved,occupied,completed',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'upcoming' => 'nullable|boolean'
            ]);

            // Construir query base
            $query = ClassScheduleSeat::with([
                'classSchedule' => function ($q) {
                    $q->with(['class', 'instructor', 'studio']);
                },
                'seat'
            ])
                ->where('user_id', $userId);

            // Aplicar filtros
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereHas('classSchedule', function ($q) use ($request) {
                    $q->where('scheduled_date', '>=', $request->date_from);
                });
            }

            if ($request->filled('date_to')) {
                $query->whereHas('classSchedule', function ($q) use ($request) {
                    $q->where('scheduled_date', '<=', $request->date_to);
                });
            }

            if ($request->boolean('upcoming')) {
                $query->whereHas('classSchedule', function ($q) {
                    $q->where('scheduled_date', '>=', now()->toDateString());
                });
            }

            // Obtener reservas ordenadas por fecha
            $seatReservations = $query->orderBy('created_at', 'desc')->get();

            if ($seatReservations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes reservas de asientos',
                    'data' => [
                        'reservations' => [],
                        'summary' => [
                            'total_reservations' => 0,
                            'upcoming_reservations' => 0,
                            'past_reservations' => 0,
                            'total_seats_reserved' => 0
                        ]
                    ]
                ])
                    ->setStatusCode(404);
            }


            // Agrupar por horario
            $groupedReservations = $seatReservations->groupBy('class_schedules_id');

            $reservations = [];
            $totalSeats = 0;
            $upcomingCount = 0;
            $pastCount = 0;

            foreach ($groupedReservations as $seats) {
                $firstSeat = $seats->first();
                $schedule = $firstSeat->classSchedule;

                // Verificar si es futuro o pasado
                $scheduleDateTime = \Carbon\Carbon::parse($schedule->scheduled_date . ' ' . $schedule->start_time);
                $isUpcoming = $scheduleDateTime->isFuture();

                if ($isUpcoming) {
                    $upcomingCount++;
                } else {
                    $pastCount++;
                }

                // Verificar si puede cancelar (al menos 2 horas antes)
                $canCancel = $isUpcoming && $scheduleDateTime->diffInHours(now()) >= 2;

                // Preparar información de asientos
                $mySeats = [];
                foreach ($seats as $seatReservation) {
                    $totalSeats++;
                    $mySeats[] = [
                        'class_schedule_seat_id' => $seatReservation->id,
                        'seat_id' => $seatReservation->seats_id,
                        'seat_number' => $seatReservation->seat->row . '.' . $seatReservation->seat->column,
                        'row' => $seatReservation->seat->row,
                        'column' => $seatReservation->seat->column,
                        'status' => $seatReservation->status,
                        'reserved_at' => $seatReservation->reserved_at?->toISOString(),
                        'expires_at' => $seatReservation->expires_at?->toISOString()
                    ];
                }

                $reservations[] = [
                    'schedule_id' => $schedule->id,
                    'class_name' => $schedule->class->name ?? 'N/A',
                    'instructor_name' => $schedule->instructor->name ?? 'N/A',
                    'studio_name' => $schedule->studio->name ?? 'N/A',
                    'scheduled_date' => $schedule->scheduled_date,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'class_status' => $schedule->status,
                    'is_upcoming' => $isUpcoming,
                    'my_seats' => $mySeats,
                    'total_my_seats' => count($mySeats),
                    'can_cancel' => $canCancel,
                    'cancellation_deadline' => $canCancel ? $scheduleDateTime->copy()->subHours(2)->toISOString() : null,
                    'class_datetime' => $scheduleDateTime->toISOString()
                ];
            }

            // Ordenar por fecha (próximas primero)
            usort($reservations, function ($a, $b) {
                if ($a['is_upcoming'] && !$b['is_upcoming']) return -1;
                if (!$a['is_upcoming'] && $b['is_upcoming']) return 1;
                return strcmp($a['class_datetime'], $b['class_datetime']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Reservas obtenidas exitosamente',
                'data' => [
                    'reservations' => $reservations,
                    'summary' => [
                        'total_reservations' => count($reservations),
                        'upcoming_reservations' => $upcomingCount,
                        'past_reservations' => $pastCount,
                        'total_seats_reserved' => $totalSeats
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener reservas del usuario', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener reservas',
                'data' => null
            ], 500);
        }
    }

    /**
     * Confirmar asistencia y marcar asientos como ocupados
     *
     * Este método se usa cuando el usuario llega a la clase y confirma su asistencia.
     * Cambia el estado de los asientos de 'reserved' a 'occupied'.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam class_schedule_seat_ids array required Los IDs de la tabla class_schedule_seat a confirmar. Example: [267, 268, 269]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Asistencia confirmada exitosamente",
     *   "data": {
     *     "confirmed_seats": [
     *       {
     *         "class_schedule_seat_id": 267,
     *         "seat_id": 1,
     *         "seat_number": "1.1",
     *         "previous_status": "reserved",
     *         "new_status": "occupied",
     *         "confirmed_at": "2025-01-11T20:30:00.000000Z"
     *       }
     *     ],
     *     "confirmation_summary": {
     *       "total_confirmed": 3,
     *       "user_id": 10,
     *       "class_name": "Yoga Matutino",
     *       "scheduled_date": "2025-01-15",
     *       "start_time": "08:00:00"
     *     }
     *   }
     * }
     */
    public function confirmAttendance(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_seat_ids' => 'required|array|min:1|max:10',
                'class_schedule_seat_ids.*' => 'required|integer|exists:class_schedule_seat,id'
            ]);

            $userId = Auth::id();
            $assignmentIds = $request->validated()['class_schedule_seat_ids'];

            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($assignmentIds, $userId) {

                // Obtener las asignaciones que pertenecen al usuario autenticado
                $assignments = ClassScheduleSeat::whereIn('id', $assignmentIds)
                    ->where('user_id', $userId)
                    ->where('status', 'reserved') // Solo asientos reservados pueden ser confirmados
                    ->with(['classSchedule.class', 'seat'])
                    ->lockForUpdate()
                    ->get();

                if ($assignments->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontraron asientos reservados para confirmar',
                        'data' => null
                    ], 400);
                }

                // Confirmar todos los asientos
                $confirmedSeats = [];
                $confirmedAt = now();

                foreach ($assignments as $assignment) {
                    $previousStatus = $assignment->status;

                    // Actualizar a ocupado
                    $assignment->update([
                        'status' => 'occupied',
                        'expires_at' => null // Ya no expira porque está confirmado
                    ]);

                    $confirmedSeats[] = [
                        'class_schedule_seat_id' => $assignment->id,
                        'seat_id' => $assignment->seats_id,
                        'seat_number' => $assignment->seat->row . '.' . $assignment->seat->column,
                        'row' => $assignment->seat->row,
                        'column' => $assignment->seat->column,
                        'previous_status' => $previousStatus,
                        'new_status' => 'occupied',
                        'confirmed_at' => $confirmedAt->toISOString()
                    ];
                }

                // Obtener información de la clase para el resumen
                $firstAssignment = $assignments->first();
                $classSchedule = $firstAssignment->classSchedule;

                // Preparar respuesta exitosa
                return response()->json([
                    'success' => true,
                    'message' => 'Asistencia confirmada exitosamente',
                    'data' => [
                        'confirmed_seats' => $confirmedSeats,
                        'confirmation_summary' => [
                            'total_confirmed' => count($confirmedSeats),
                            'user_id' => $userId,
                            'schedule_id' => $classSchedule->id,
                            'class_name' => $classSchedule->class->name ?? 'N/A',
                            'scheduled_date' => $classSchedule->scheduled_date,
                            'start_time' => $classSchedule->start_time,
                            'confirmed_at' => $confirmedAt->toISOString()
                        ]
                    ]
                ], 200);
            });
        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error al confirmar asistencia', [
                'user_id' => Auth::id(),
                'assignment_ids' => $request->input('class_schedule_seat_ids', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al confirmar asistencia',
                'data' => null
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de paquetes para un horario específico
     *
     * Permite verificar si el usuario tiene paquetes disponibles para la disciplina
     * de una clase específica antes de intentar hacer una reserva.
     *
     * @summary Verificar paquetes disponibles para horario
     * @operationId checkPackageAvailability
     * @tags Validación de Paquetes
     *
     * @param  \App\Models\ClassSchedule  $classSchedule Horario de clase
     * @return \Illuminate\Http\JsonResponse
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Paquetes disponibles encontrados",
     *   "data": {
     *     "can_reserve": true,
     *     "discipline_required": {
     *       "id": 1,
     *       "name": "Yoga"
     *     },
     *     "available_packages": [
     *       {
     *         "id": 42,
     *         "package_code": "PKG001-2024",
     *         "package_name": "Paquete Yoga 10 Clases",
     *         "remaining_classes": 9,
     *         "expiry_date": "2025-02-15",
     *         "days_remaining": 35
     *       }
     *     ]
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "No tienes paquetes disponibles para la disciplina 'Yoga'",
     *   "data": {
     *     "can_reserve": false,
     *     "discipline_required": {
     *       "id": 1,
     *       "name": "Yoga"
     *     },
     *     "available_packages": []
     *   }
     * }
     */
    public function checkPackageAvailability(ClassSchedule $classSchedule)
    {
        try {
            $userId = Auth::id();
            $packageValidationService = new PackageValidationService();

            $validation = $packageValidationService->validateUserPackagesForSchedule($classSchedule, $userId);

            return response()->json([
                'success' => $validation['valid'],
                'message' => $validation['message'],
                'data' => [
                    'can_reserve' => $validation['valid'],
                    'discipline_required' => $validation['discipline_required'],
                    'available_packages' => $validation['available_packages']
                ]
            ], $validation['valid'] ? 200 : 422);
        } catch (\Exception $e) {
            Log::error('Error al verificar disponibilidad de paquetes', [
                'user_id' => Auth::id(),
                'schedule_id' => $classSchedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al verificar paquetes',
                'data' => null
            ], 500);
        }
    }



}
