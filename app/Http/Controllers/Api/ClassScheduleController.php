<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassScheduleIndexRequest;
use App\Http\Requests\ReserveSeatsRequest;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\ClassScheduleSeatResource;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleSeat;
use App\Models\FootwearReservation;
use App\Models\WaitingClass;
use App\Mail\WaitingListSeatAssignedMailable;
use App\Services\PackageValidationService;
use Error;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @tags Horarios de Clases
 */
final class ClassScheduleController extends Controller
{
    /**
     * Lista todos los horarios de clases programados
     *
     */
    public function index(ClassScheduleIndexRequest $request)
    {
        try {

            // Validar parámetros (si no usas ClassScheduleIndexRequest, usa Validator::make)
            $validated = $request->validated(); // Esto funciona si ya tienes el Form Request configurado

            $query = ClassSchedule::query()
                ->where('status', 'scheduled')
                ->with(['class', 'instructor', 'studio']);

            // 🎯 Calcular la fecha mínima basada en la membresía del usuario
            $userId = Auth::id();

            if ($userId) {
                // Obtener todas las membresías activas del usuario
                $userMemberships = \App\Models\UserMembership::where('user_id', $userId)
                    ->where('status', 'active')
                    ->where('expiry_date', '>=', now())
                    ->whereHas('membership')
                    ->with('membership')
                    ->get();

                if ($userMemberships->isNotEmpty()) {
                    // Encontrar el máximo classes_before entre todas las membresías
                    $maxClassesBefore = $userMemberships->max(function ($userMembership) {
                        return $userMembership->membership->classes_before ?? 0;
                    });

                    if ($maxClassesBefore > 0) {
                        // Si el usuario tiene membresía con classes_before, puede ver clases desde hoy + X días adelante
                        // Ejemplo: si classes_before = 7, puede ver clases hasta 7 días en el futuro
                        $query->where('scheduled_date', '<=', now()->addDays($maxClassesBefore));
                    }
                }
            }

            // Si no tiene membresía o classes_before = 0, solo mostrar clases de hoy en adelante
            $query->where('scheduled_date', '>=', now()->toDateString());

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

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de horarios obtenida exitosamente',
                'datoAdicional' => ClassScheduleResource::collection($schedules)
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener lista de horarios',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }

    /**
     * Obtiene un horario de clase específico
     */
    public function show(Request $request)
    {

        try {

            $request->validate([
                'classSchedule_id' => 'required|exists:class_schedules,id'
            ]);
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

            $schedule = $query->findOrFail($request->classSchedule_id);

            // Convertir a array y agregar información específica del usuario
            $scheduleData = (new ClassScheduleResource($schedule))->toArray(request());

            // Siempre incluir información de asientos del usuario si está autenticado
            $userId = Auth::id();

            // Obtener asientos del usuario en este horario
            // Incluir asientos donde el usuario es el propietario (user_id) O donde viene de lista de espera (user_waiting_id)
            $userSeats = ClassScheduleSeat::where('class_schedules_id', $schedule->id)
                ->where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhere('user_waiting_id', $userId);
                })
                ->with('seat')
                ->get();

            if ($userSeats->isNotEmpty()) {
                $userSeatsFormatted = $userSeats->map(function ($seatAssignment) {
                    return [
                        'class_schedule_seat_id' => $seatAssignment->id,
                        'seat_id' => $seatAssignment->seats_id,
                        'seat_number' => $seatAssignment->seat?->seat_number ?? null,
                        'row' => $seatAssignment->seat?->row ?? null,
                        'column' => $seatAssignment->seat?->column ?? null,
                        'status' => $seatAssignment->status,
                        'reserved_at' => $seatAssignment->reserved_at?->toISOString(),
                        'expires_at' => $seatAssignment->expires_at?->toISOString()
                    ];
                });

                $scheduleData['my_seats'] = $userSeatsFormatted;
                $scheduleData['total_my_seats'] = $userSeatsFormatted->count();

                // Actualizar seats_summary con información específica del usuario
                $scheduleData['seats_summary'] = [
                    'total_seats' => $scheduleData['seats_summary']['total_seats'] ?? 0,
                    'available_count' => $scheduleData['seats_summary']['available_count'] ?? 0,
                    'reserved_count' => $scheduleData['seats_summary']['reserved_count'] ?? 0,
                    'occupied_count' => $scheduleData['seats_summary']['occupied_count'] ?? 0,
                    'blocked_count' => $scheduleData['seats_summary']['blocked_count'] ?? 0,
                    'my_reserved_seats' => $userSeats->where('status', 'reserved')->count(),
                    'my_occupied_seats' => $userSeats->where('status', 'occupied')->count(),
                    'my_blocked_seats' => $userSeats->where('status', 'blocked')->count(),
                ];
            } else {
                $scheduleData['my_seats'] = [];
                $scheduleData['total_my_seats'] = 0;
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Horario obtenido exitosamente',
                'datoAdicional' => $scheduleData
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener horario',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }


    /**
     * Obtiene el mapa de asientos de un horario de clase
     *
     */
    public function getSeatMap(Request $request)
    {
        $request->validate([
            'classSchedule_id' => 'required|exists:class_schedules,id'
        ]);

        try {

            $classSchedule = ClassSchedule::findOrFail($request->classSchedule_id);

            // Verificar que el horario está activo
            if ($classSchedule->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Horario de clase no disponible',
                    'data' => null
                ], 422);
            }

            // Verificar si el horario está en progreso
            if ($classSchedule->status === 'in_progress') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El horario se encuentra en curso',
                    'datoAdicional' => null
                ], 200);
            }

            // Cargar las relaciones necesarias
            $classSchedule->load(['studio', 'class.discipline', 'instructor']);

            // Verificar que el horario tiene un estudio asignado
            if (!$classSchedule->studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay estudio asignado a este horario',
                    'data' => null
                ], 422);
            }

            // Obtener el mapa de asientos del modelo
            $seatMapData = $classSchedule->getSeatMap();

            // Log para debugging
            Log::info('Datos del mapa de asientos', [
                'class_schedule_id' => $classSchedule->id,
                'summary_data' => $seatMapData['summary'] ?? 'no summary'
            ]);

            // Verificar que el método devolvió datos válidos
            if (!$seatMapData || (is_array($seatMapData) && empty($seatMapData))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay asientos configurados para este horario',
                    'data' => null
                ], 422);
            }

            // Estructurar la respuesta usando los datos del modelo
            $responseData = [
                'studio' => [
                    'id' => $classSchedule->studio->id,
                    'name' => $classSchedule->studio->name,
                    'max_capacity' => $classSchedule->studio->max_capacity,
                    'rows' => $classSchedule->studio->row,
                    'columns' => $classSchedule->studio->column,
                    'location' => $classSchedule->studio->location,
                    'is_zigzag' => (bool) $classSchedule->studio->zigzag,
                ],
                'class' => [
                    'id' => $classSchedule->class->id,
                    'name' => $classSchedule->class->name,
                    'discipline' => $classSchedule->class->discipline->name ?? 'N/A',
                    'available_seats' => $classSchedule->class->available_seats,
                    'discipline_img' => $classSchedule->class->discipline->icon_url ? asset('storage/') . '/' . $classSchedule->class->discipline->icon_url : asset('default/icon.png'),
                    'discipline_img_seat' => $classSchedule->class->discipline->image_seat ? asset('storage/') . '/' . $classSchedule->class->discipline->image_seat : asset('default/icon.png'),
                ],
                'instructor' => [
                    'id' => $classSchedule->instructor->id,
                    'name' => $classSchedule->instructor->name,
                    'profile_image' => $classSchedule->instructor->profile_image ? asset('storage/') . '/' . $classSchedule->instructor->profile_image : asset('default/entrenador.jpg'),
                ],
                'seat_map' => $seatMapData['seat_grid'] ?? [],
                'summary' => [
                    'total_seats' => $seatMapData['summary']['total_seats'] ?? 0,
                    'available' => $seatMapData['summary']['available_count'] ?? 0,
                    'reserved' => $seatMapData['summary']['reserved_count'] ?? 0,
                    'occupied' => $seatMapData['summary']['occupied_count'] ?? 0,
                    'blocked' => $seatMapData['summary']['blocked_count'] ?? 0
                ]
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Mapa de asientos obtenido exitosamente',
                'datoAdicional' => $responseData
            ], 200);
        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error al obtener mapa de asientos', [
                'class_schedule_id' => $classSchedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al obtener el mapa de asientos',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Reservar múltiples asientos para el usuario autenticado

     */
    public function reserveSeats(ReserveSeatsRequest $request)
    {
        // Validar parámetros (si no usas ClassScheduleIndexRequest, usa Validator::make)
        $validated = $request->validated(); // Esto funciona si ya tienes el Form Request configurado

        try {

            $classSchedule = ClassSchedule::findOrFail($request->class_schedule_id);
            $classSchedule->loadMissing('class');
            // Log inicial para debugging

            Log::info('Iniciando reserva de asientos', [
                'schedule_id' => $classSchedule->id,
                'request_data' => $request->validated(),
                'user_id' => Auth::id()
            ]);

            // Verificar que el horario permite reservas
            if ($classSchedule->status === 'cancelled') {

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' =>  'No se puede reservar en un horario cancelado',
                    'datoAdicional' => ['reason' => 'schedule_cancelled']
                ], 200);
            }

            // Permitir reservar hasta 10 minutos después del inicio
            $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                ? $classSchedule->scheduled_date->format('Y-m-d')
                : $classSchedule->scheduled_date;

            $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $classSchedule->start_time);
            $limitToReserve = $startDateTime->copy()->addMinutes(10);

            if (now()->greaterThan($limitToReserve)) {

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' =>  'No se puede reservar después de los 10 minutos del inicio de la clase',
                    'datoAdicional' => [
                        'reason' => 'too_late',
                        'start_time' => $startDateTime->toDateTimeString(),
                        'limit_time' => $limitToReserve->toDateTimeString(),
                        'now' => now()->toDateTimeString()
                    ]
                ], 200);
            }


            $userId = Auth::id();
            $classScheduleSeatIds = $request->validated()['class_schedule_seat_ids'];
            $minutesToExpire = $request->validated()['minutes_to_expire'];

            $maxSeatsPerReservation = $classSchedule->class?->available_seats;

            // ✅ Validar que no se reserven más asientos que los permitidos para esta clase
            if ($maxSeatsPerReservation !== null && count($classScheduleSeatIds) > $maxSeatsPerReservation) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No puedes reservar más asientos de los permitidos para esta clase',
                    'datoAdicional' => [
                        'reason' => 'max_seats_exceeded',
                        'requested_seats' => count($classScheduleSeatIds),
                        'max_allowed' => $maxSeatsPerReservation
                    ]
                ], 200);
            }

            // 🎯 VALIDAR PAQUETES DISPONIBLES PARA LA DISCIPLINA
            $packageValidationService = new PackageValidationService();
            $packageValidation = $packageValidationService->validateUserPackagesForSchedule($classSchedule, $userId);

            if (!$packageValidation['valid']) {


                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' =>  $packageValidation['message'],
                    'datoAdicional' => [
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
                // Obtener los paquetes y membresías disponibles como modelos Eloquent
                $classSchedule->load(['class.discipline']);
                $disciplineId = $classSchedule->class->discipline_id;
                $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline($userId, $disciplineId);

                // Obtener membresías directas para la disciplina
                $availableMemberships = $packageValidationService->getUserAvailableMembershipsForDiscipline($userId, $disciplineId);

                // 🎯 Obtener membresías adicionales cuya disciplina está en el grupo de disciplinas de los paquetes del usuario
                $membershipsFromPackageGroups = $packageValidationService->getUserMembershipsFromPackageDisciplineGroups($userId, $disciplineId);

                // Combinar ambas colecciones (membresías directas + membresías del grupo de paquetes)
                $availableMemberships = $availableMemberships->merge($membershipsFromPackageGroups)->unique('id');

                // Calcular total de asientos disponibles (paquetes + membresías)
                $totalAvailableSeats = $availablePackages->sum('remaining_classes') + $availableMemberships->sum('remaining_free_classes');

                // Log del orden de consumo de paquetes y membresías
                Log::info('Orden de consumo de paquetes y membresías (más cercanos a vencer primero)', [
                    'user_id' => $userId,
                    'schedule_id' => $classSchedule->id,
                    'packages_order' => $availablePackages->map(function ($package) {
                        return [
                            'package_id' => $package->id,
                            'package_code' => $package->package_code,
                            'package_name' => $package->package->name ?? 'N/A',
                            'remaining_classes' => $package->remaining_classes,
                            'expiry_date' => $package->expiry_date?->toDateString(),
                            'days_remaining' => $package->days_remaining,
                            'type' => 'package'
                        ];
                    })->toArray(),
                    'memberships_order' => $availableMemberships->map(function ($membership) {
                        return [
                            'membership_id' => $membership->id,
                            'membership_name' => $membership->membership->name ?? 'N/A',
                            'discipline_name' => $membership->discipline->name ?? 'N/A',
                            'remaining_free_classes' => $membership->remaining_free_classes,
                            'expiry_date' => $membership->expiry_date?->toDateString(),
                            'days_remaining' => $membership->days_remaining,
                            'type' => 'membership'
                        ];
                    })->toArray()
                ]);

                // Validar que el usuario no reserve más asientos de los que tiene disponibles
                if (count($classScheduleSeatIds) > $totalAvailableSeats) {

                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No tienes suficientes asientos disponibles en tus paquetes para reservar esta cantidad.',
                        'datoAdicional' => [
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
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Algunos asientos no están asignados a este horario',
                        'datoAdicional' =>  [
                            'missing_assignment_ids' => $missingAssignmentIds,
                            'available_assignment_ids' => $foundAssignmentIds
                        ]
                    ], 200);
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
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Algunos asientos no están disponibles',
                        'datoAdicional' => [
                            'unavailable_seats' => $unavailableSeats,
                            'available_seats' => collect($availableAssignments)->pluck('id')->toArray()
                        ]
                    ], 200);
                }

                // Reservar todos los asientos disponibles
                $reservedSeats = [];
                $reservedAt = now();
                $expiresAt = $reservedAt->copy()->addMinutes($minutesToExpire);
                $consumptionDetails = [];

                // Consumir clases priorizando membresías sobre paquetes
                foreach ($availableAssignments as $i => $assignment) {
                    // Usar el servicio para consumir la mejor opción disponible
                    $consumptionResult = $packageValidationService->consumeClassFromBestOption($userId, $disciplineId);

                    if (!$consumptionResult['success']) {
                        // Esto no debería ocurrir por la validación previa
                        Log::error('Error al consumir clase para reserva', [
                            'user_id' => $userId,
                            'schedule_id' => $classSchedule->id,
                            'assignment_id' => $assignment->id,
                            'error' => $consumptionResult['message']
                        ]);
                        break;
                    }

                    // Determinar qué tipo de consumo se realizó
                    $consumedItem = null;
                    $consumedType = null;

                    if (isset($consumptionResult['consumed_membership'])) {
                        $consumedItem = $consumptionResult['consumed_membership'];
                        $consumedType = 'membership';
                    } elseif (isset($consumptionResult['consumed_package'])) {
                        $consumedItem = $consumptionResult['consumed_package'];
                        $consumedType = 'package';
                    }

                    if ($consumedItem) {
                        $consumptionDetails[] = $consumedItem;

                        // Actualizar el asiento con la información correspondiente
                        $updateData = [
                            'user_id' => $userId,
                            'status' => 'reserved',
                            'reserved_at' => $reservedAt,
                            'expires_at' => $expiresAt,
                        ];

                        // Agregar el ID correspondiente según el tipo
                        if ($consumedType === 'membership') {
                            $updateData['user_membership_id'] = $consumedItem['id'];
                        } else {
                            $updateData['user_package_id'] = $consumedItem['id'];
                        }

                        $assignment->update($updateData);

                        // Cargar la relación del asiento para obtener información completa
                        $assignment->load('seat');

                        $reservedSeats[] = [
                            'class_schedule_seat_id' => $assignment->id,
                            'seat_id' => $assignment->seats_id,
                            'seat_number' => $assignment->seat?->seat_number ?? null,
                            'row' => $assignment->seat?->row ?? null,
                            'column' => $assignment->seat?->column ?? null,
                            'status' => 'reserved',
                            'user_id' => $userId,
                            'reserved_at' => $reservedAt->toISOString(),
                            'expires_at' => $expiresAt->toISOString(),
                            'assignment_id' => $assignment->id,
                            'schedule_id' => $classSchedule->id,
                            'consumed_type' => $consumedType,
                            'consumed_item' => $consumedItem
                        ];
                    }
                }


                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Asientos reservados exitosamente',
                    'datoAdicional' => [
                        'class_schedule_id' => $classSchedule->id
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
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al reservar asientos',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Liberar/cancelar todas las reservas de asientos del usuario en un horario específico
     *
     */
    public function releaseSeats(Request $request)
    {
        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id'
            ]);

            $userId = Auth::id();
            $classScheduleId = $validated['class_schedule_id'];

            // Usar transacción para asegurar consistencia
            return DB::transaction(function () use ($classScheduleId, $userId) {

                // Obtener el horario de clase y verificar su estado
                $classSchedule = ClassSchedule::findOrFail($classScheduleId);

                // 🚫 Solo se puede cancelar si el horario está en estado 'scheduled'
                if ($classSchedule->status !== 'scheduled') {
                    $message = 'No se pueden liberar asientos. ';

                    if ($classSchedule->status === 'in_progress') {
                        $message .= 'El horario se encuentra en curso';
                    } elseif ($classSchedule->status === 'cancelled') {
                        $message .= 'El horario ha sido cancelado';
                    } elseif ($classSchedule->status === 'completed') {
                        $message .= 'El horario ya ha finalizado';
                    } else {
                        $message .= 'El horario no está programado';
                    }

                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => $message,
                        'datoAdicional' => null
                    ], 200);
                }

                // 🚫 Validar que NO se pueda cancelar si falta menos de 1 hora para iniciar
                $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                    ? $classSchedule->scheduled_date->format('Y-m-d')
                    : $classSchedule->scheduled_date;

                $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $classSchedule->start_time);
                $oneHourBefore = $startDateTime->copy()->subHour();

                if (now()->greaterThanOrEqualTo($oneHourBefore)) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No se puede cancelar la reserva. La clase inicia en menos de 1 hora',
                        'datoAdicional' => [
                            'reason' => 'too_close_to_start',
                            'start_time' => $startDateTime->toDateTimeString(),
                            'one_hour_before' => $oneHourBefore->toDateTimeString(),
                            'current_time' => now()->toDateTimeString()
                        ]
                    ], 200);
                }

                // Obtener todas las asignaciones del usuario en este horario
                $assignments = ClassScheduleSeat::where('class_schedules_id', $classScheduleId)
                    ->where('user_id', $userId) // Solo puede liberar sus propias reservas
                    ->lockForUpdate()
                    ->get();

                // Verificar si el usuario tiene asientos reservados
                if ($assignments->isEmpty()) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No tienes asientos reservados en este horario',
                        'datoAdicional' => [
                            'class_schedule_id' => $classScheduleId,
                            'user_id' => $userId
                        ]
                    ], 200);
                }

                // Liberar todos los asientos
                $releasedSeats = [];
                $releasedAt = now();
                $refundedPackages = [];

                foreach ($assignments as $assignment) {
                    $previousStatus = $assignment->status;
                    $previousUserPackageId = $assignment->user_package_id;
                    $previousUserMembershipId = $assignment->user_membership_id;

                    // Cargar la relación del asiento
                    $assignment->load('seat');

                    // Si tenía un paquete asignado, devolver la clase
                    if ($previousUserPackageId) {
                        $userPackage = \App\Models\UserPackage::find($previousUserPackageId);
                        if ($userPackage && $userPackage->user_id === $userId) {
                            $userPackage->refundClasses(1);
                            $refundedPackages[] = [
                                'package_id' => $userPackage->id,
                                'package_code' => $userPackage->package_code,
                                'package_name' => $userPackage->package->name ?? 'N/A',
                                'classes_refunded' => 1,
                                'remaining_classes' => $userPackage->remaining_classes,
                                'type' => 'package'
                            ];
                        }
                    }

                    // Si tenía una membresía asignada, devolver la clase gratis
                    if ($previousUserMembershipId) {
                        $userMembership = \App\Models\UserMembership::find($previousUserMembershipId);
                        if ($userMembership && $userMembership->user_id === $userId) {
                            $userMembership->refundFreeClasses(1);
                            $refundedPackages[] = [
                                'membership_id' => $userMembership->id,
                                'membership_name' => $userMembership->membership->name ?? 'N/A',
                                'discipline_name' => $userMembership->discipline->name ?? 'N/A',
                                'classes_refunded' => 1,
                                'remaining_free_classes' => $userMembership->remaining_free_classes,
                                'type' => 'membership'
                            ];
                        }
                    }

                    // Actualizar a disponible
                    $assignment->update([
                        'user_id' => null,
                        'status' => 'available',
                        'reserved_at' => null,
                        'expires_at' => null,
                        'user_package_id' => null,
                        'user_membership_id' => null
                    ]);

                    $releasedSeats[] = [
                        'class_schedule_seat_id' => $assignment->id,
                        'seat_id' => $assignment->seats_id,
                        'seat_number' => $assignment->seat?->seat_number ?? null,
                        'row' => $assignment->seat?->row ?? null,
                        'column' => $assignment->seat?->column ?? null,
                        'previous_status' => $previousStatus,
                        'new_status' => 'available',
                        'released_at' => $releasedAt->toISOString(),
                        'user_package_id' => $previousUserPackageId
                    ];
                }

                // 🎯 Cancelar automáticamente las reservas de zapatos del usuario para esta clase
                $footwearReservations = FootwearReservation::where('class_schedules_id', $classScheduleId)
                    ->where('user_client_id', $userId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->get();

                $canceledFootwearCount = 0;
                $canceledFootwearBySize = [];

                if ($footwearReservations->isNotEmpty()) {
                    // Cargar relaciones para obtener información de tallas
                    $footwearReservations->load('footwear');

                    // Agrupar por talla antes de cancelar
                    foreach ($footwearReservations as $reservation) {
                        $footwear = $reservation->footwear;
                        if ($footwear && $footwear->size) {
                            $size = $footwear->size;
                            if (!isset($canceledFootwearBySize[$size])) {
                                $canceledFootwearBySize[$size] = 0;
                            }
                            $canceledFootwearBySize[$size]++;
                        }
                    }

                    // Cancelar todas las reservas de zapatos
                    $canceledFootwearCount = FootwearReservation::where('class_schedules_id', $classScheduleId)
                        ->where('user_client_id', $userId)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->update(['status' => 'canceled']);

                    Log::info('Reservas de zapatos canceladas automáticamente al liberar asientos', [
                        'class_schedule_id' => $classScheduleId,
                        'user_id' => $userId,
                        'total_reservations_canceled' => $canceledFootwearCount,
                        'canceled_by_size' => $canceledFootwearBySize
                    ]);
                }

                // 🎯 Asignar automáticamente asientos liberados a usuarios de la lista de espera
                $assignedFromWaitingList = [];
                $packageValidationService = new PackageValidationService();

                // Cargar relaciones necesarias del horario
                $classSchedule->load(['class.discipline', 'instructor', 'studio']);
                $disciplineId = $classSchedule->class->discipline_id;

                // Obtener usuarios en lista de espera ordenados por fecha de creación (FIFO)
                $waitingListUsers = WaitingClass::where('class_schedules_id', $classScheduleId)
                    ->whereIn('status', ['waiting', 'notified'])
                    ->with(['user', 'userPackage'])
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                // Obtener asientos disponibles (los que acabamos de liberar)
                $availableSeats = ClassScheduleSeat::where('class_schedules_id', $classScheduleId)
                    ->where('status', 'available')
                    ->whereIn('id', collect($releasedSeats)->pluck('class_schedule_seat_id')->toArray())
                    ->with('seat')
                    ->lockForUpdate()
                    ->get();

                // Asignar asientos a usuarios de la lista de espera
                foreach ($availableSeats as $seat) {
                    if ($waitingListUsers->isEmpty()) {
                        break; // No hay más usuarios en lista de espera
                    }

                    // Obtener el siguiente usuario de la lista de espera
                    $waitingUser = $waitingListUsers->shift();

                    // Verificar que el usuario no tenga ya un asiento asignado en este horario
                    $existingAssignment = ClassScheduleSeat::where('class_schedules_id', $classScheduleId)
                        ->where(function($q) use ($waitingUser) {
                            $q->where('user_id', $waitingUser->user_id)
                              ->orWhere('user_waiting_id', $waitingUser->user_id);
                        })
                        ->whereIn('status', ['reserved', 'occupied'])
                        ->where('id', '!=', $seat->id)
                        ->first();

                    if ($existingAssignment) {
                        // Este usuario ya tiene un asiento, saltar y continuar con el siguiente
                        $waitingListUsers->prepend($waitingUser); // Devolver al inicio de la cola
                        continue;
                    }

                    // Consumir clase del usuario (priorizando membresías sobre paquetes)
                    $consumptionResult = $packageValidationService->consumeClassFromBestOption($waitingUser->user_id, $disciplineId);

                    if (!$consumptionResult['success']) {
                        // No se pudo consumir la clase, saltar este usuario
                        Log::warning('No se pudo consumir clase para usuario de lista de espera', [
                            'waiting_user_id' => $waitingUser->id,
                            'user_id' => $waitingUser->user_id,
                            'schedule_id' => $classScheduleId,
                            'error' => $consumptionResult['message'] ?? 'Error desconocido'
                        ]);
                        $waitingListUsers->prepend($waitingUser); // Devolver al inicio de la cola
                        continue;
                    }

                    // Determinar qué tipo de consumo se realizó
                    $userPackageId = null;
                    $userMembershipId = null;

                    if (isset($consumptionResult['consumed_membership'])) {
                        $userMembershipId = $consumptionResult['consumed_membership']['id'];
                    } elseif (isset($consumptionResult['consumed_package'])) {
                        $userPackageId = $consumptionResult['consumed_package']['id'];
                    }

                    // Calcular fecha de expiración (10 minutos después del inicio de la clase)
                    $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                        ? $classSchedule->scheduled_date->format('Y-m-d')
                        : $classSchedule->scheduled_date;
                    $startDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $classSchedule->start_time);
                    $expiresAt = $startDateTime->copy()->addMinutes(10);

                    // Asignar el asiento al usuario de la lista de espera
                    // Asignar tanto user_id como user_waiting_id para que el usuario pueda ver su reserva
                    $seat->update([
                        'user_id' => $waitingUser->user_id,
                        'user_waiting_id' => $waitingUser->user_id,
                        'status' => 'reserved',
                        'reserved_at' => now(),
                        'expires_at' => $expiresAt,
                        'user_package_id' => $userPackageId,
                        'user_membership_id' => $userMembershipId
                    ]);

                    // Actualizar el estado de la lista de espera a 'confirmed'
                    $waitingUser->update([
                        'status' => 'confirmed'
                    ]);

                    // Cargar relaciones para el correo
                    $waitingUser->load('user');
                    $seat->load('seat');

                    // Enviar correo al usuario
                    try {
                        Mail::to($waitingUser->user->email)->send(
                            new WaitingListSeatAssignedMailable(
                                $waitingUser->user,
                                $classSchedule,
                                $seat->seat?->seat_number
                            )
                        );
                    } catch (\Exception $emailException) {
                        Log::error('Error al enviar correo a usuario de lista de espera', [
                            'user_id' => $waitingUser->user_id,
                            'email' => $waitingUser->user->email,
                            'error' => $emailException->getMessage()
                        ]);
                    }

                    $assignedFromWaitingList[] = [
                        'class_schedule_seat_id' => $seat->id,
                        'seat_id' => $seat->seats_id,
                        'seat_number' => $seat->seat?->seat_number ?? null,
                        'row' => $seat->seat?->row ?? null,
                        'column' => $seat->seat?->column ?? null,
                        'waiting_user_id' => $waitingUser->id,
                        'user_id' => $waitingUser->user_id,
                        'user_name' => $waitingUser->user->name ?? 'N/A',
                        'user_email' => $waitingUser->user->email ?? 'N/A',
                        'assigned_at' => now()->toISOString(),
                        'consumption_type' => $userMembershipId ? 'membership' : 'package',
                        'consumption_details' => $consumptionResult['consumed_membership'] ?? $consumptionResult['consumed_package'] ?? null
                    ];

                    Log::info('Asiento asignado automáticamente desde lista de espera', [
                        'class_schedule_id' => $classScheduleId,
                        'waiting_user_id' => $waitingUser->id,
                        'user_id' => $waitingUser->user_id,
                        'seat_id' => $seat->id,
                        'seat_number' => $seat->seat?->seat_number
                    ]);
                }

                // Preparar respuesta exitosa
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Reservas liberadas exitosamente',
                    'datoAdicional' =>  [
                        'class_schedule_id' => $classScheduleId,
                        'released_seats' => $releasedSeats,
                        'release_summary' => [
                            'total_released' => count($releasedSeats),
                            'user_id' => $userId,
                            'released_at' => $releasedAt->toISOString()
                        ],
                        'refunded_packages' => $refundedPackages,
                        'footwear_cancellation' => [
                            'total_reservations_canceled' => $canceledFootwearCount,
                            'canceled_by_size' => $canceledFootwearBySize,
                            'note' => 'Las reservas de zapatos fueron canceladas automáticamente al liberar los asientos'
                        ],
                        'waiting_list_assignments' => [
                            'total_assigned' => count($assignedFromWaitingList),
                            'assigned_users' => $assignedFromWaitingList,
                            'note' => count($assignedFromWaitingList) > 0
                                ? 'Los asientos liberados fueron asignados automáticamente a usuarios de la lista de espera. Se enviaron correos de notificación.'
                                : 'No había usuarios en lista de espera o no se pudieron asignar los asientos.'
                        ]
                    ]
                ], 200);
            });
        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error al liberar asientos', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $request->input('class_schedule_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al liberar asientos',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Mis reservas de clases con filtros por estado
     */
    public function getMyReservations(Request $request)
    {
        try {
            $userId = Auth::id();

            // Validar parámetros de filtro
            $request->validate([
                'status' => 'sometimes|array',
                'status.*' => 'string|in:reserved,occupied,completed,lost',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
                'upcoming' => 'sometimes|boolean',
                'per_page' => 'sometimes|integer|min:1|max:50',
                'page' => 'sometimes|integer|min:1'
            ]);

            // Construir query base para obtener horarios directamente
            $query = ClassSchedule::with([
                'class.discipline',
                'instructor',
                'studio',
                'classScheduleSeats' => function ($q) use ($userId, $request) {
                    // Incluir asientos donde el usuario es el propietario (user_id) O donde viene de lista de espera (user_waiting_id)
                    $q->where(function($query) use ($userId) {
                        $query->where('user_id', $userId)
                              ->orWhere('user_waiting_id', $userId);
                    })->with('seat');
                    // Si se especifica status (uno o varios), filtrar los asientos
                    if ($request->filled('status')) {
                        $statuses = is_array($request->status) ? $request->status : [$request->status];
                        $q->whereIn('status', $statuses);
                    }
                }
            ])
                ->whereHas('classScheduleSeats', function ($q) use ($userId, $request) {
                    // Incluir asientos donde el usuario es el propietario (user_id) O donde viene de lista de espera (user_waiting_id)
                    $q->where(function($query) use ($userId) {
                        $query->where('user_id', $userId)
                              ->orWhere('user_waiting_id', $userId);
                    });
                    // Si se especifica status (uno o varios), filtrar solo esos asientos
                    if ($request->filled('status')) {
                        $statuses = is_array($request->status) ? $request->status : [$request->status];
                        $q->whereIn('status', $statuses);
                    }
                });

            // Aplicar filtros de fecha
            if ($request->filled('date_from')) {
                $query->where('scheduled_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('scheduled_date', '<=', $request->date_to);
            }

            // Filtro para clases próximas (futuras)
            if ($request->boolean('upcoming', false)) {
                $query->where('scheduled_date', '>=', now()->toDateString());
            }

            // Aplicar paginación si se solicita
            if ($request->has('per_page')) {
                $classSchedules = $query->orderBy('scheduled_date', 'asc')
                    ->orderBy('start_time', 'asc')
                    ->paginate(
                        perPage: $request->integer('per_page', 15),
                        page: $request->integer('page', 1)
                    );

                $summary = $this->calculateReservationsSummary($classSchedules->items());

                // Formatear reservas con información específica del usuario
                $formattedReservations = $classSchedules->map(function ($schedule) use ($userId) {
                    $userSeats = $schedule->classScheduleSeats->map(function ($seatAssignment) {
                        return [
                            'class_schedule_seat_id' => $seatAssignment->id,
                            'seat_id' => $seatAssignment->seats_id,
                            'seat_number' => $seatAssignment->seat?->seat_number ?? null,
                            'row' => $seatAssignment->seat?->row ?? null,
                            'column' => $seatAssignment->seat?->column ?? null,
                            'status' => $seatAssignment->status,
                            'reserved_at' => $seatAssignment->reserved_at?->toISOString(),
                            'expires_at' => $seatAssignment->expires_at?->toISOString()
                        ];
                    });

                    $scheduleData = (new ClassScheduleResource($schedule))->toArray(request());
                    $scheduleData['my_seats'] = $userSeats;
                    $scheduleData['total_my_seats'] = $userSeats->count();

                    // Actualizar seats_summary con información específica del usuario
                    $scheduleData['seats_summary'] = [
                        'total_seats' => $userSeats->count(),
                        'available_count' => 0, // No aplicable para reservas del usuario
                        'reserved_count' => $userSeats->where('status', 'reserved')->count(),
                        'occupied_count' => $userSeats->where('status', 'occupied')->count(),
                        'blocked_count' => $userSeats->where('status', 'blocked')->count(),
                    ];

                    return $scheduleData;
                });

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Reservas obtenidas exitosamente',
                    'datoAdicional' => [
                        'reservations' => $formattedReservations,
                        'summary' => $summary,
                        'pagination' => [
                            'current_page' => $classSchedules->currentPage(),
                            'last_page' => $classSchedules->lastPage(),
                            'per_page' => $classSchedules->perPage(),
                            'total' => $classSchedules->total(),
                            'from' => $classSchedules->firstItem(),
                            'to' => $classSchedules->lastItem(),
                            'has_more_pages' => $classSchedules->hasMorePages(),
                        ]
                    ]
                ], 200);
            } else {
                // Sin paginación - obtener todos los resultados
                $classSchedules = $query->orderBy('scheduled_date', 'asc')
                    ->orderBy('start_time', 'asc')
                    ->get();

                if ($classSchedules->isEmpty()) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No tienes reservas de asientos',
                        'datoAdicional' => [
                            'reservations' => [],
                            'summary' => [
                                'total_reservations' => 0,
                                'upcoming_reservations' => 0,
                                'past_reservations' => 0,
                                'total_seats_reserved' => 0
                            ]
                        ]
                    ], 200);
                }

                $summary = $this->calculateReservationsSummary($classSchedules->toArray());

                // Formatear reservas con información específica del usuario
                $formattedReservations = $classSchedules->map(function ($schedule) use ($userId) {
                    $userSeats = $schedule->classScheduleSeats->map(function ($seatAssignment) {
                        return [
                            'class_schedule_seat_id' => $seatAssignment->id,
                            'seat_id' => $seatAssignment->seats_id,
                            'seat_number' => $seatAssignment->seat?->seat_number ?? null,
                            'row' => $seatAssignment->seat?->row ?? null,
                            'column' => $seatAssignment->seat?->column ?? null,
                            'status' => $seatAssignment->status,
                            'reserved_at' => $seatAssignment->reserved_at?->toISOString(),
                            'expires_at' => $seatAssignment->expires_at?->toISOString()
                        ];
                    });

                    $scheduleData = (new ClassScheduleResource($schedule))->toArray(request());
                    $scheduleData['my_seats'] = $userSeats;
                    $scheduleData['total_my_seats'] = $userSeats->count();

                    // Actualizar seats_summary con información específica del usuario
                    $scheduleData['seats_summary'] = [
                        'total_seats' => $userSeats->count(),
                        'available_count' => 0, // No aplicable para reservas del usuario
                        'reserved_count' => $userSeats->where('status', 'reserved')->count(),
                        'occupied_count' => $userSeats->where('status', 'occupied')->count(),
                        'blocked_count' => $userSeats->where('status', 'blocked')->count(),
                    ];

                    return $scheduleData;
                });

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Reservas obtenidas exitosamente',
                    'datoAdicional' => [
                        'reservations' => $formattedReservations,
                        'summary' => $summary
                    ]
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener reservas del usuario', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al obtener reservas',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Calcular resumen de reservas
     */
    private function calculateReservationsSummary($classSchedules)
    {
        $totalReservations = count($classSchedules);
        $upcomingCount = 0;
        $pastCount = 0;
        $totalSeats = 0;

        foreach ($classSchedules as $schedule) {
            // Contar asientos del usuario
            $userSeatsCount = count($schedule['classScheduleSeats'] ?? []);
            $totalSeats += $userSeatsCount;

            // Verificar si es futuro o pasado
            // Manejar diferentes formatos de fecha
            if (strpos($schedule['scheduled_date'], 'T') !== false || strpos($schedule['scheduled_date'], ' ') !== false) {
                // Si ya contiene tiempo completo, usar directamente
                $scheduleDateTime = \Carbon\Carbon::parse($schedule['scheduled_date']);
            } else {
                // Si solo contiene fecha, concatenar con hora
                $scheduleDateTime = \Carbon\Carbon::parse($schedule['scheduled_date'] . ' ' . $schedule['start_time']);
            }

            if ($scheduleDateTime->isFuture()) {
                $upcomingCount++;
            } else {
                $pastCount++;
            }
        }

        return [
            'total_reservations' => $totalReservations,
            'upcoming_reservations' => $upcomingCount,
            'past_reservations' => $pastCount,
            'total_seats_reserved' => $totalSeats
        ];
    }



    /**
     * Verificar disponibilidad de paquetes para un horario específico
     */
    public function checkPackageAvailability(Request $request)
    {
        $request->validate([
            'classSchedule_id' => 'required|exists:class_schedules,id'
        ]);

        try {

            $classSchedule = ClassSchedule::findOrFail($request->classSchedule_id);

            if (!$classSchedule) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No existe el horario',
                    'datoAdicional' => null
                ], 200);
            }

            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => [
                        'reason' => 'unauthenticated'
                    ]
                ], 401);
            }

            // ✅ Validar si el usuario ya tiene una reserva existente en este horario
            $existingSeats = ClassScheduleSeat::where('class_schedules_id', $classSchedule->id)
                ->where('user_id', $userId)
                ->whereIn('status', ['reserved', 'occupied', 'completed'])
                ->with('seat')
                ->get();

            if ($existingSeats->isNotEmpty()) {
                $existingSeatsSummary = $existingSeats->map(function ($seatAssignment) {
                    return [
                        'class_schedule_seat_id' => $seatAssignment->id,
                        'seat_id' => $seatAssignment->seats_id,
                        'seat_number' => $seatAssignment->seat?->seat_number,
                        'status' => $seatAssignment->status,
                        'reserved_at' => $seatAssignment->reserved_at?->toISOString(),
                        'consumption_source' => $seatAssignment->user_package_id ? 'package' : ($seatAssignment->user_membership_id ? 'membership' : null),
                        'user_package_id' => $seatAssignment->user_package_id,
                        'user_membership_id' => $seatAssignment->user_membership_id,
                    ];
                });

                Log::info('Usuario ya cuenta con asientos reservados en el horario', [
                    'user_id' => $userId,
                    'schedule_id' => $classSchedule->id,
                    'existing_reservations' => $existingSeatsSummary,
                ]);

                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'Ya tienes asientos reservados en este horario',
                    'datoAdicional' => [
                        'can_reserve' => false,
                        'reason' => 'already_reserved',
                        'existing_reservations' => $existingSeatsSummary,
                        'summary' => [
                            'total_existing_reservations' => $existingSeats->count(),
                            'statuses' => $existingSeats->groupBy('status')->map->count(),
                        ]
                    ]
                ], 200);
            }

            $packageValidationService = new PackageValidationService();

            $validation = $packageValidationService->validateUserPackagesForSchedule($classSchedule, $userId);

            // Calcular resumen de paquetes con múltiples disciplinas
            $availablePackages = $validation['available_packages'] ?? [];
            $availableMemberships = $validation['available_memberships'] ?? [];

            // 🎯 Las membresías ya incluyen tanto las directas como las del grupo de disciplinas de los paquetes
            // (manejado automáticamente por validateUserPackagesForSchedule)
            $summary = [
                'total_available_packages' => count($availablePackages),
                'total_available_memberships' => count($availableMemberships),
                'multi_discipline_packages' => collect($availablePackages)->where('is_multi_discipline', true)->count(),
                'single_discipline_packages' => collect($availablePackages)->where('is_multi_discipline', false)->count(),
            ];

            // 🎯 Determinar si puede reservar: debe haber paquetes o membresías disponibles
            // Incluso si validateUserPackagesForSchedule retorna valid: false, si hay paquetes o membresías, puede reservar
            $canReserve = (count($availablePackages) > 0 || count($availableMemberships) > 0) || $validation['valid'];

            // Log para debugging
            Log::info('Verificación de disponibilidad de paquetes', [
                'user_id' => $userId,
                'schedule_id' => $classSchedule->id,
                'discipline_required' => $validation['discipline_required'],
                'validation_valid' => $validation['valid'],
                'available_packages_count' => count($availablePackages),
                'available_memberships_count' => count($availableMemberships),
                'can_reserve' => $canReserve,
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $canReserve
                    ? 'Horario disponible para el paquete'
                    : $validation['message'],
                'datoAdicional' => [
                    'can_reserve' => $canReserve,
                    'discipline_required' => $validation['discipline_required'],
                    'available_packages' => $availablePackages,
                    'available_memberships' => $validation['available_memberships'] ?? [],
                    'validation_message' => $validation['message'] ?? 'Validación completada',
                    'summary' => $summary,
                    'note' => 'Los paquetes con múltiples disciplinas (is_multi_discipline: true) pueden usarse para cualquier disciplina incluida en el paquete'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al verificar disponibilidad de paquetes', [
                'user_id' => Auth::id(),
                'schedule_id' => $classSchedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al verificar paquetes',
                'datoAdicional' => null
            ], 200);
        }
    }



    /**
     * Obtiene los asientos reservados por el usuario autenticado en un horario específico
     */
    public function reservedShow(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id'
            ]);

            $userId = Auth::id();
            $classScheduleId = $request->integer('class_schedule_id');

            // Obtener el horario de clase con sus relaciones y contadores de asientos
            $classSchedule = ClassSchedule::with(['class.discipline', 'instructor', 'studio'])
                ->withCount([
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
                ])
                ->findOrFail($classScheduleId);

            // Obtener todos los asientos reservados por el usuario en este horario
            // Incluir asientos donde el usuario es el propietario (user_id) O donde viene de lista de espera (user_waiting_id)
            $userSeats = ClassScheduleSeat::with(['seat', 'userPackage.package', 'userMembership.membership'])
                ->where('class_schedules_id', $classScheduleId)
                ->where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhere('user_waiting_id', $userId);
                })
                ->get();

            if ($userSeats->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes asientos reservados en este horario',
                    'datoAdicional' => null
                ], 200);
            }

            // Convertir a la estructura estándar usando ClassScheduleResource
            $scheduleData = (new ClassScheduleResource($classSchedule))->toArray(request());

            // Formatear asientos del usuario con la misma estructura
            $userSeatsFormatted = $userSeats->map(function ($seatAssignment) {
                $seatData = [
                    'class_schedule_seat_id' => $seatAssignment->id,
                    'seat_id' => $seatAssignment->seats_id,
                    'seat_number' => $seatAssignment->seat?->seat_number ?? null,
                    'row' => $seatAssignment->seat?->row ?? null,
                    'column' => $seatAssignment->seat?->column ?? null,
                    'status' => $seatAssignment->status,
                    'reserved_at' => $seatAssignment->reserved_at?->toISOString(),
                    'expires_at' => $seatAssignment->expires_at?->toISOString()
                ];

                // Agregar información del paquete si existe
                if ($seatAssignment->userPackage) {
                    $seatData['user_package_info'] = [
                        'package_id' => $seatAssignment->userPackage->id,
                        'package_code' => $seatAssignment->userPackage->package_code,
                        'package_name' => $seatAssignment->userPackage->package->name ?? 'N/A'
                    ];
                }

                // Agregar información de la membresía si existe
                if ($seatAssignment->userMembership) {
                    $seatData['user_membership_info'] = [
                        'membership_id' => $seatAssignment->userMembership->id,
                        'membership_name' => $seatAssignment->userMembership->membership->name ?? 'N/A',
                        'discipline_name' => $seatAssignment->userMembership->discipline->name ?? 'N/A'
                    ];
                }

                return $seatData;
            });

            // Agregar información específica del usuario
            $scheduleData['my_seats'] = $userSeatsFormatted;
            $scheduleData['total_my_seats'] = $userSeatsFormatted->count();

            // Actualizar seats_summary con información específica del usuario
            $scheduleData['seats_summary'] = [
                'total_seats' => $scheduleData['seats_summary']['total_seats'] ?? 0,
                'available_count' => $scheduleData['seats_summary']['available_count'] ?? 0,
                'reserved_count' => $scheduleData['seats_summary']['reserved_count'] ?? 0,
                'occupied_count' => $scheduleData['seats_summary']['occupied_count'] ?? 0,
                'blocked_count' => $scheduleData['seats_summary']['blocked_count'] ?? 0,
                'my_reserved_seats' => $userSeats->where('status', 'reserved')->count(),
                'my_occupied_seats' => $userSeats->where('status', 'occupied')->count(),
                'my_blocked_seats' => $userSeats->where('status', 'blocked')->count(),
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Asientos reservados obtenidos exitosamente',
                'datoAdicional' => $scheduleData
            ], 200);
        } catch (\Throwable $e) {
            // Log del error para debugging
            Log::error('Error al obtener asientos reservados del usuario', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $request->input('class_schedule_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al obtener asientos reservados',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
