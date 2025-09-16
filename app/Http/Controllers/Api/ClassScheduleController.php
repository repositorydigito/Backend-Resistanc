<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClassScheduleIndexRequest;
use App\Http\Requests\ReserveSeatsRequest;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\ClassScheduleSeatResource;
use App\Models\ClassSchedule;
use App\Models\ClassScheduleSeat;
use App\Models\WaitingClass;
use App\Services\PackageValidationService;
use Error;
use Illuminate\Http\JsonResponse;
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
     */
    public function index(ClassScheduleIndexRequest $request)
    {
        try {

            // Validar parámetros (si no usas ClassScheduleIndexRequest, usa Validator::make)
            $validated = $request->validated(); // Esto funciona si ya tienes el Form Request configurado

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


            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Horario obtenido exitosamente',
                'datoAdicional' => new ClassScheduleResource($schedule)
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
                    'location' => $classSchedule->studio->location
                ],
                'class' => [
                    'id' => $classSchedule->class->id,
                    'name' => $classSchedule->class->name,
                    'discipline' => $classSchedule->class->discipline->name ?? 'N/A',
                    'discipline_img' => $classSchedule->class->discipline->icon_url ? asset('storage/') . '/' . $classSchedule->class->discipline->icon_url : null,
                    'discipline_img_seat' => $classSchedule->class->discipline->image_seat ? asset('storage/') . '/' . $classSchedule->class->discipline->image_seat : null,
                ],
                'instructor' => [
                    'id' => $classSchedule->instructor->id,
                    'name' => $classSchedule->instructor->name,
                    'profile_image' => $classSchedule->instructor->profile_image ? asset('storage/') . '/' . $classSchedule->instructor->profile_image : null,
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
                $availableMemberships = $packageValidationService->getUserAvailableMembershipsForDiscipline($userId, $disciplineId);

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
                            'seat_number' => $assignment->seat->row . '.' . $assignment->seat->column,
                            'row' => $assignment->seat->row,
                            'column' => $assignment->seat->column,
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
     * Liberar/cancelar reserva de asientos usando el ID de class_schedule_seat
     *
     */
    public function releaseSeats(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_seat_ids' => 'required|array|min:1|max:50',
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
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'Algunos asientos no pueden ser liberados (no existen o no te pertenecen)',
                        'datoAdicional' => [
                            'invalid_assignment_ids' => $missingIds,
                            'valid_assignment_ids' => $foundIds
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
                        'seat_number' => $assignment->seat->row . '.' . $assignment->seat->column,
                        'row' => $assignment->seat->row,
                        'column' => $assignment->seat->column,
                        'previous_status' => $previousStatus,
                        'new_status' => 'available',
                        'released_at' => $releasedAt->toISOString(),
                        'user_package_id' => $previousUserPackageId
                    ];
                }

                // Preparar respuesta exitosa

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Reservas liberadas exitosamente',
                    'datoAdicional' =>  [
                        'released_seats' => $releasedSeats,
                        'release_summary' => [
                            'total_released' => count($releasedSeats),
                            'user_id' => $userId,
                            'released_at' => $releasedAt->toISOString()
                        ],
                        'refunded_packages' => $refundedPackages
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
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Algunos asientos no pueden ser liberados (no existen o no te pertenecen)',
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
                'status' => 'sometimes|string|in:reserved,occupied,completed',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from',
                // 'upcoming' => 'sometimes|boolean',
                'per_page' => 'sometimes|integer|min:1|max:50',
                'page' => 'sometimes|integer|min:1'
            ]);

            // Construir query base para obtener horarios directamente
            $query = ClassSchedule::with([
                'class.discipline',
                'instructor',
                'studio',
                'classScheduleSeats' => function ($q) use ($userId) {
                    $q->where('user_id', $userId)->with('seat');
                }
            ])
                ->whereHas('classScheduleSeats', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });

            // Aplicar filtros
            if ($request->filled('status')) {
                $query->whereHas('classScheduleSeats', function ($q) use ($request, $userId) {
                    $q->where('user_id', $userId)->where('status', $request->status);
                });
            }

            if ($request->filled('date_from')) {
                $query->where('scheduled_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('scheduled_date', '<=', $request->date_to);
            }

            // if ($request->boolean('upcoming')) {
            //     $query->where('scheduled_date', '>=', now()->toDateString());
            // }

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
                            'seat_number' => $seatAssignment->seat->row . '.' . $seatAssignment->seat->column,
                            'row' => $seatAssignment->seat->row,
                            'column' => $seatAssignment->seat->column,
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
                            'seat_number' => $seatAssignment->seat->row . '.' . $seatAssignment->seat->column,
                            'row' => $seatAssignment->seat->row,
                            'column' => $seatAssignment->seat->column,
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
     * Confirmar asistencia y marcar asientos como ocupados
     *
     */
    // public function confirmAttendance(Request $request)
    // {
    //     try {
    //         // Validar datos de entrada
    //         $request->validate([
    //             'class_schedule_seat_ids' => 'required|array|min:1|max:50',
    //             'class_schedule_seat_ids.*' => 'required|integer|exists:class_schedule_seat,id'
    //         ]);

    //         $userId = Auth::id();
    //         $assignmentIds = $request->validated()['class_schedule_seat_ids'];

    //         // Usar transacción para asegurar consistencia
    //         return DB::transaction(function () use ($assignmentIds, $userId) {

    //             // Obtener las asignaciones que pertenecen al usuario autenticado
    //             $assignments = ClassScheduleSeat::whereIn('id', $assignmentIds)
    //                 ->where('user_id', $userId)
    //                 ->where('status', 'reserved') // Solo asientos reservados pueden ser confirmados
    //                 ->with(['classSchedule.class', 'seat'])
    //                 ->lockForUpdate()
    //                 ->get();

    //             if ($assignments->isEmpty()) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'No se encontraron asientos reservados para confirmar',
    //                     'data' => null
    //                 ], 400);
    //             }

    //             // Confirmar todos los asientos
    //             $confirmedSeats = [];
    //             $confirmedAt = now();

    //             foreach ($assignments as $assignment) {
    //                 $previousStatus = $assignment->status;

    //                 // Actualizar a ocupado
    //                 $assignment->update([
    //                     'status' => 'occupied',
    //                     'expires_at' => null // Ya no expira porque está confirmado
    //                 ]);

    //                 $confirmedSeats[] = [
    //                     'class_schedule_seat_id' => $assignment->id,
    //                     'seat_id' => $assignment->seats_id,
    //                     'seat_number' => $assignment->seat->row . '.' . $assignment->seat->column,
    //                     'row' => $assignment->seat->row,
    //                     'column' => $assignment->seat->column,
    //                     'previous_status' => $previousStatus,
    //                     'new_status' => 'occupied',
    //                     'confirmed_at' => $confirmedAt->toISOString()
    //                 ];
    //             }

    //             // Obtener información de la clase para el resumen
    //             $firstAssignment = $assignments->first();
    //             $classSchedule = $firstAssignment->classSchedule;

    //             // Preparar respuesta exitosa
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Asistencia confirmada exitosamente',
    //                 'data' => [
    //                     'confirmed_seats' => $confirmedSeats,
    //                     'confirmation_summary' => [
    //                         'total_confirmed' => count($confirmedSeats),
    //                         'user_id' => $userId,
    //                         'schedule_id' => $classSchedule->id,
    //                         'class_name' => $classSchedule->class->name ?? 'N/A',
    //                         'scheduled_date' => $classSchedule->scheduled_date,
    //                         'start_time' => $classSchedule->start_time,
    //                         'confirmed_at' => $confirmedAt->toISOString()
    //                     ]
    //                 ]
    //             ], 200);
    //         });
    //     } catch (\Exception $e) {
    //         // Log del error para debugging
    //         Log::error('Error al confirmar asistencia', [
    //             'user_id' => Auth::id(),
    //             'assignment_ids' => $request->input('class_schedule_seat_ids', []),
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error interno al confirmar asistencia',
    //             'data' => null
    //         ], 500);
    //     }
    // }

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
            $packageValidationService = new PackageValidationService();

            $validation = $packageValidationService->validateUserPackagesForSchedule($classSchedule, $userId);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Horario disponible para el paquete',
                'datoAdicional' => [
                    'can_reserve' => $validation['valid'],
                    'discipline_required' => $validation['discipline_required'],
                    'available_packages' => $validation['available_packages']
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

            // Obtener el horario de clase con sus relaciones
            $classSchedule = ClassSchedule::with(['class.discipline', 'instructor', 'studio'])
                ->findOrFail($classScheduleId);

            // Obtener todos los asientos reservados por el usuario en este horario
            $userSeats = ClassScheduleSeat::with(['seat', 'userPackage.package'])
                ->where('class_schedules_id', $classScheduleId)
                ->where('user_id', $userId)
                ->get();

            if ($userSeats->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes asientos reservados en este horario',
                    'datoAdicional' => null
                ], 200);
            }

            // Obtener el mapa completo de asientos del horario
            $seatMapData = $classSchedule->getSeatMap();

            // Preparar información del horario
            $scheduleInfo = [
                'id' => $classSchedule->id,
                'class_name' => $classSchedule->class->name ?? 'N/A',
                'instructor_name' => $classSchedule->instructor->name ?? 'N/A',
                'studio_name' => $classSchedule->studio->name ?? 'N/A',
                'scheduled_date' => $classSchedule->scheduled_date,
                'start_time' => $classSchedule->start_time,
                'end_time' => $classSchedule->end_time,
                'status' => $classSchedule->status
            ];

            // Preparar información de los asientos del usuario
            $formattedUserSeats = $userSeats->map(function ($seatAssignment) {
                $seatData = [
                    'class_schedule_seat_id' => $seatAssignment->id,
                    'seat_id' => $seatAssignment->seats_id,
                    'seat_number' => $seatAssignment->seat->row . '.' . $seatAssignment->seat->column,
                    'row' => $seatAssignment->seat->row,
                    'column' => $seatAssignment->seat->column,
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

                return $seatData;
            });

            // Preparar resumen
            $summary = [
                'total_seats_reserved' => $userSeats->count(),
                'user_id' => $userId,
                'schedule_id' => $classScheduleId
            ];

            // Marcar los asientos del usuario en el mapa
            $userSeatIds = $userSeats->pluck('seats_id')->toArray();
            $enhancedSeatGrid = [];

            if (isset($seatMapData['seat_grid'])) {
                foreach ($seatMapData['seat_grid'] as $rowIndex => $row) {
                    $enhancedRow = [];
                    foreach ($row as $seat) {
                        $enhancedSeat = $seat;
                        // Verificar que el asiento tenga la clave 'id' antes de usarla
                        if (isset($seat['id']) && in_array($seat['id'], $userSeatIds)) {
                            $enhancedSeat['is_mine'] = true;
                            $enhancedSeat['my_status'] = $userSeats->firstWhere('seats_id', $seat['id'])->status;
                        } else {
                            $enhancedSeat['is_mine'] = false;
                            $enhancedSeat['my_status'] = null;
                        }
                        $enhancedRow[] = $enhancedSeat;
                    }
                    $enhancedSeatGrid[] = $enhancedRow;
                }
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Asientos reservados obtenidos exitosamente',
                'datoAdicional' => [
                    'schedule_info' => $scheduleInfo,
                    'user_seats' => $formattedUserSeats,
                    'summary' => $summary,
                    'seat_map' => [
                        'studio' => [
                            'id' => $classSchedule->studio->id,
                            'name' => $classSchedule->studio->name,
                            'max_capacity' => $classSchedule->studio->max_capacity,
                            'rows' => $classSchedule->studio->row,
                            'columns' => $classSchedule->studio->column,
                            'location' => $classSchedule->studio->location
                        ],
                        'seat_grid' => $enhancedSeatGrid,
                        'summary' => [
                            'total_seats' => $seatMapData['summary']['total_seats'] ?? 0,
                            'available' => $seatMapData['summary']['available_count'] ?? 0,
                            'reserved' => $seatMapData['summary']['reserved_count'] ?? 0,
                            'occupied' => $seatMapData['summary']['occupied_count'] ?? 0,
                            'blocked' => $seatMapData['summary']['blocked_count'] ?? 0
                        ]
                    ],
                    'my_reservation_summary' => [
                        'total_my_seats' => $userSeats->count(),
                        'my_reserved_seats' => $userSeats->where('status', 'reserved')->count(),
                        'my_occupied_seats' => $userSeats->where('status', 'occupied')->count(),
                        'my_blocked_seats' => $userSeats->where('status', 'blocked')->count(),
                    ]
                ]
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
