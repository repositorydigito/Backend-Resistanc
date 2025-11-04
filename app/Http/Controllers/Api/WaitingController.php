<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\WaitingResource;
use App\Models\ClassSchedule;
use App\Models\WaitingClass;
use App\Services\PackageValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


/**
 * @tags Lista de espera
 */
final class WaitingController extends Controller
{
    /**
     * Listar entradas en la lista de espera del usuario autenticado
     *
     * @return \Illuminate\Http\JsonResponse
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     */


    public function indexWaitingList()
    {
        try {
            $userId = Auth::id();

            // Obtener todas las entradas en lista de espera del usuario
            $waitingList = WaitingClass::where('user_id', $userId)
                ->where('status', 'waiting')
                ->with(['classSchedule.class', 'classSchedule.studio', 'classSchedule.instructor'])
                ->orderBy('created_at', 'asc')
                ->get();

            if ($waitingList->isEmpty()) {
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'No tienes entradas en lista de espera',
                    'datoAdicional' => []
                ], 200);
            }

            // Agrupar por horario de clase
            $groupedBySchedule = $waitingList->groupBy('class_schedules_id');

            $formattedData = [];
            $totalSchedules = 0;
            $totalWaitingEntries = 0;

            foreach ($groupedBySchedule as $scheduleId => $waitingEntries) {
                $firstEntry = $waitingEntries->first();
                $schedule = $firstEntry->classSchedule;

                $totalSchedules++;
                $totalWaitingEntries += $waitingEntries->count();

                $formattedData[] = [
                    'schedule_info' => [
                        'id' => $schedule->id,
                        'scheduled_date' => $schedule->scheduled_date,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'status' => $schedule->status,
                        'max_capacity' => $schedule->max_capacity,
                        'available_spots' => $schedule->available_spots,
                        'booked_spots' => $schedule->booked_spots,
                        'waitlist_spots' => $schedule->waitlist_spots,
                        'theme' => $schedule->theme ?? null
                    ],
                    'class' => [
                        'id' => $schedule->class->id,
                        'name' => $schedule->class->name,
                        'color_hex' => $schedule->class->color_hex,
                        'icon_url' => $schedule->class->icon_url ? asset('storage/') . '/' . $schedule->class->icon_url : asset('default/icon.png'),
                        'discipline' => $schedule->class->discipline->name ?? 'N/A',
                        'img_url' => $schedule->class->img_url ? asset('storage/') . '/' . $schedule->class->img_url : asset('default/class.jpg'),
                        'discipline_img' => $schedule->class->discipline->icon_url ? asset('storage/') . '/' . $schedule->class->discipline->icon_url : asset('default/icon.png'),
                    ],
                    'instructor' => [
                        'id' => $schedule->instructor->id,
                        'name' => $schedule->instructor->name,
                        'profile_image' => $schedule->instructor->profile_image ? asset('storage/') . '/' . $schedule->instructor->profile_image : asset('default/entrenador.jpg'),
                        'rating_average' => $schedule->instructor->rating_average ?? null,
                        'is_head_coach' => $schedule->instructor->is_head_coach ?? false,
                    ],
                    'studio' => [
                        'id' => $schedule->studio->id,
                        'name' => $schedule->studio->name,
                        'location' => $schedule->studio->location ?? 'N/A',
                    ],
                    'waiting_entries' => [
                        'total_entries' => $waitingEntries->count(),
                        'entries' => $waitingEntries->map(function ($entry) {
                            return [
                                'id' => $entry->id,
                                'status' => $entry->status,
                                'created_at' => $entry->created_at->toISOString(),
                                'updated_at' => $entry->updated_at->toISOString()
                            ];
                        })
                    ],
                    'position_info' => [
                        'my_position' => $waitingEntries->first()->id, // ID de la primera entrada (posición más antigua)
                        'total_people_ahead' => WaitingClass::where('class_schedules_id', $scheduleId)
                            ->where('status', 'waiting')
                            ->where('id', '<', $waitingEntries->first()->id)
                            ->count()
                    ]
                ];
            }

            // Ordenar por fecha de clase (próximas primero)
            usort($formattedData, function ($a, $b) {
                // Asegurar que la fecha esté en formato correcto
                $dateA = $a['schedule_info']['scheduled_date'];
                $timeA = $a['schedule_info']['start_time'];

                $dateB = $b['schedule_info']['scheduled_date'];
                $timeB = $b['schedule_info']['start_time'];

                // Si la fecha ya es un objeto Carbon, convertir a string
                if ($dateA instanceof \Carbon\Carbon) {
                    $dateA = $dateA->format('Y-m-d');
                }
                if ($dateB instanceof \Carbon\Carbon) {
                    $dateB = $dateB->format('Y-m-d');
                }

                $dateTimeA = \Carbon\Carbon::parse($dateA . ' ' . $timeA);
                $dateTimeB = \Carbon\Carbon::parse($dateB . ' ' . $timeB);

                // Usar timestamp para comparar
                if ($dateTimeA->timestamp == $dateTimeB->timestamp) {
                    return 0;
                }
                return ($dateTimeA->timestamp < $dateTimeB->timestamp) ? -1 : 1;
            });

            // $formattedData['summary'] = [
            //     'total_schedules' => $totalSchedules,
            //     'total_waiting_entries' => $totalWaitingEntries
            // ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de espera obtenida exitosamente',
                'datoAdicional' => $formattedData,



            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al obtener lista de espera', [
                'user_id' => Auth::id(),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'message' => 'Error interno al obtener lista de espera',
                // 'datoAdicional' => null
                'datoAdicional' => $th->getMessage(),
            ], 200);
        }
    }


    /**
     * Obtener lista de espera para un horario específico
     *
     * @return \Illuminate\Http\JsonResponse
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     */

    public function show(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer|exists:class_schedules,id',
            ]);


            $classScheduleId = $request->input('id');
            $userId = Auth::id();



            // Verificar que el horario existe
            $classSchedule = ClassSchedule::with(['class.discipline', 'studio', 'instructor'])
                ->find($classScheduleId);

            if (!$classSchedule) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El horario de clase no existe',
                    'datoAdicional' => null
                ], 404);
            }

            // Obtener todas las entradas en lista de espera del usuario para este horario
            $waitingList = WaitingClass::where('user_id', $userId)
                ->where('class_schedules_id', $classScheduleId)
                ->where('status', 'waiting')
                ->with(['classSchedule.class.discipline', 'classSchedule.studio', 'classSchedule.instructor', 'user'])
                ->orderBy('created_at', 'asc')
                ->get();

            // Obtener TODA la lista de espera para este horario ordenada correctamente
            $allWaitingList = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('status', 'waiting')
                ->join('users', 'waiting_classes.user_id', '=', 'users.id')
                ->select('waiting_classes.*')
                ->orderBy('waiting_classes.created_at', 'asc')
                ->orderBy('waiting_classes.id', 'asc') // Ordenar por ID en caso de empate en created_at
                ->get()
                ->load('user'); // Cargar la relación user después del JOIN

            // Calcular la posición del usuario en la lista de espera
            $userPosition = null;
            $totalPeopleAhead = 0;

            if (!$waitingList->isEmpty()) {
                // Encontrar la primera entrada del usuario en la lista ordenada
                $userFirstEntry = $waitingList->first();

                // Buscar la posición de esta entrada en la lista completa ordenada
                $position = 1;
                foreach ($allWaitingList as $entry) {
                    if ($entry->id === $userFirstEntry->id) {
                        $userPosition = $position;
                        $totalPeopleAhead = $position - 1;
                        break;
                    }
                    $position++;
                }
            }

            // Si no tiene entradas en lista de espera, retornar información del horario sin lista de espera
            if ($waitingList->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes entradas en lista de espera para este horario',
                    'datoAdicional' => null
                ], 200);
            }

            // Si tiene entradas en lista de espera, usar el resource
            $waitingEntries = $waitingList->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'status' => $entry->status,
                    'created_at' => $entry->created_at->toISOString(),
                    'updated_at' => $entry->updated_at->toISOString()
                ];
            });

            $formattedData = [
                'classSchedule' => new ClassScheduleResource($classSchedule),
                'waiting_entries' => [
                    'total_entries' => $waitingList->count(),
                    'entries' => $waitingEntries
                ],
                'position_info' => [
                    'my_position' => $userPosition,
                    'total_people_ahead' => $totalPeopleAhead,
                    'total_waiting_list' => $allWaitingList->count(),
                    'waiting_list_order' => $allWaitingList->map(function ($entry, $index) use ($userId) {
                        return [
                            'position' => $index + 1,
                            'waiting_id' => $entry->id,
                            'user_id' => $entry->user_id,
                            'user_name' => $entry->user->name,
                            'created_at' => $entry->created_at->toISOString(),
                            'is_user' => $entry->user_id === $userId
                        ];
                    })
                ],
                'has_waiting_list' => true
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de espera obtenida exitosamente',
                'datoAdicional' => $formattedData
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al obtener lista de espera', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $request->input('id'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al obtener lista de espera',
                'datoAdicional' => $th->getMessage(),
            ], 200);
        }
    }


    /**
     * Agregar a la lista de espera
     *
     * @return \Illuminate\Http\JsonResponse
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     */


    public function addWaitingList(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $userId = Auth::id();
            $classScheduleId = $request->input('class_schedule_id');
            $quantity = $request->input('quantity');

            // Obtener el horario de clase con sus relaciones
            $classSchedule = ClassSchedule::with(['class.discipline'])->findOrFail($classScheduleId);

            // Verificar que el horario está activo
            if ($classSchedule->status === 'cancelled') {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No se puede agregar a la lista de espera de un horario cancelado',
                    'datoAdicional' => ['reason' => 'schedule_cancelled']
                ], 200);
            }

            // Verificar que no sea un horario pasado
            $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                ? $classSchedule->scheduled_date->format('Y-m-d')
                : $classSchedule->scheduled_date;

            $scheduleDateTime = \Carbon\Carbon::parse($scheduledDate . ' ' . $classSchedule->start_time);
            if ($scheduleDateTime->isPast()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No se puede agregar a la lista de espera de un horario pasado',
                    'datoAdicional' => ['reason' => 'schedule_past']
                ], 200);
            }

            // 🎯 VALIDAR PAQUETES DISPONIBLES PARA LA DISCIPLINA
            $packageValidationService = new PackageValidationService();
            $packageValidation = $packageValidationService->validateUserPackagesForSchedule($classSchedule, $userId);

            if (!$packageValidation['valid']) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => $packageValidation['message'],
                    'datoAdicional' => [
                        'reason' => 'insufficient_packages',
                        'discipline_required' => $packageValidation['discipline_required'],
                        'available_packages' => $packageValidation['available_packages']
                    ]
                ], 200);
            }

            // Verificar cuántas veces el usuario ya está en la lista de espera para este horario
            $existingWaitingCount = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('user_id', $userId)
                ->where('status', 'waiting')
                ->count();

            // Si el usuario ya está en la lista de espera, informar y retornar el ID del horario
            if ($existingWaitingCount > 0) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 2,
                    'mensajeUsuario' => 'Ya te encuentras en la lista de espera para este horario',
                    'datoAdicional' => [
                        'reason' => 'already_in_waiting_list',
                        'class_schedule_id' => $classScheduleId,
                        'current_waiting_count' => $existingWaitingCount,
                        'note' => 'Usa el class_schedule_id para consultar los detalles de la lista de espera'
                    ]
                ], 200);
            }

            // Obtener la cantidad total de asientos disponibles en los paquetes del usuario
            $disciplineId = $classSchedule->class->discipline_id;
            $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline($userId, $disciplineId);
            
            // También obtener membresías disponibles (directas + del grupo de disciplinas)
            $availableMemberships = $packageValidationService->getUserAvailableMembershipsForDiscipline($userId, $disciplineId);
            
            // 🎯 Obtener membresías adicionales cuya disciplina está en el grupo de disciplinas de los paquetes del usuario
            $membershipsFromPackageGroups = $packageValidationService->getUserMembershipsFromPackageDisciplineGroups($userId, $disciplineId);
            
            // Combinar ambas colecciones (membresías directas + membresías del grupo de paquetes)
            $availableMemberships = $availableMemberships->merge($membershipsFromPackageGroups)->unique('id');
            
            // Calcular total de clases disponibles (paquetes + membresías)
            $totalAvailableSeats = $availablePackages->sum('remaining_classes') + $availableMemberships->sum('remaining_free_classes');

            // Verificar si la cantidad solicitada excede los asientos disponibles
            $totalRequestedEntries = $existingWaitingCount + $quantity;
            if ($totalRequestedEntries > $totalAvailableSeats) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'La cantidad solicitada excede los asientos disponibles en tus paquetes',
                    'datoAdicional' => [
                        'reason' => 'insufficient_available_seats',
                        'current_waiting_count' => $existingWaitingCount,
                        'requested_quantity' => $quantity,
                        'total_requested' => $totalRequestedEntries,
                        'max_allowed' => $totalAvailableSeats,
                        'available_packages_count' => $availablePackages->count(),
                        'available_memberships_count' => $availableMemberships->count()
                    ]
                ], 200);
            }

            // Usar transacción para asegurar consistencia al consumir clases
            $createdEntries = [];
            $consumedPackages = [];
            $consumedMemberships = [];
            $currentTime = now();
            $disciplineId = $classSchedule->class->discipline_id;

            \Illuminate\Support\Facades\DB::transaction(function () use (
                $quantity, 
                $userId, 
                $classScheduleId, 
                $disciplineId, 
                $packageValidationService,
                $currentTime,
                &$createdEntries,
                &$consumedPackages,
                &$consumedMemberships
            ) {
                // Crear múltiples entradas en la lista de espera consumiendo clases inmediatamente
                for ($i = 0; $i < $quantity; $i++) {
                    // Consumir clase priorizando membresías sobre paquetes
                    $consumptionResult = $packageValidationService->consumeClassFromBestOption($userId, $disciplineId);
                    
                    if (!$consumptionResult['success']) {
                        throw new \Exception($consumptionResult['message'] ?? 'No se pudo consumir la clase');
                    }

                    $userPackageId = null;
                    
                    // Guardar información de lo que se consumió
                    if (isset($consumptionResult['consumed_membership'])) {
                        // Se consumió de una membresía
                        $consumedMemberships[] = $consumptionResult['consumed_membership'];
                        $userPackageId = null; // Las membresías no se guardan en user_package_id
                    } elseif (isset($consumptionResult['consumed_package'])) {
                        // Se consumió de un paquete
                        $consumedPackages[] = $consumptionResult['consumed_package'];
                        $userPackageId = $consumptionResult['consumed_package']['id'];
                    }
                    
                    $waitingEntry = WaitingClass::create([
                        'class_schedules_id' => $classScheduleId,
                        'user_id' => $userId,
                        'user_package_id' => $userPackageId, // Guardar ID del paquete si se usó, null si fue membresía
                        'status' => 'waiting',
                        'created_at' => $currentTime,
                        'updated_at' => $currentTime,
                    ]);
                    
                    $createdEntries[] = $waitingEntry;
                }
            });

            // Log de consumo exitoso
            Log::info('Usuario agregado a lista de espera - clases consumidas', [
                'user_id' => $userId,
                'schedule_id' => $classScheduleId,
                'quantity' => $quantity,
                'discipline_required' => $packageValidation['discipline_required'],
                'consumed_packages_count' => count($consumedPackages),
                'consumed_memberships_count' => count($consumedMemberships),
            ]);

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => "Se agregaron {$quantity} entradas a la lista de espera exitosamente",
                'datoAdicional' => [
                    'waiting_entries' => $createdEntries,
                    'waiting_summary' => [
                        'quantity_added' => $quantity,
                        'total_waiting_entries' => $existingWaitingCount + $quantity,
                        'max_allowed_entries' => $totalAvailableSeats,
                        'remaining_entries' => $totalAvailableSeats - ($existingWaitingCount + $quantity)
                    ],
                    'consumption_details' => [
                        'consumed_packages' => $consumedPackages,
                        'consumed_memberships' => $consumedMemberships,
                        'total_consumed' => count($consumedPackages) + count($consumedMemberships),
                        'note' => 'Las clases han sido consumidas inmediatamente al agregar a la lista de espera. Si el usuario abandona la lista o se cancela, se deberá reembolsar manualmente.'
                    ]
                ]
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al agregar a la lista de espera', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $request->input('class_schedule_id'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al agregar a la lista de espera',
                'datoAdicional' => null
            ], 200);
        }
    }


    /**
     * Verificar si el usuario está en la lista de espera de un horario específico
     *
     * @return \Illuminate\Http\JsonResponse
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     */

    public function checkWaitingStatus(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'id' => 'required|integer|exists:class_schedules,id',
            ]);

            $userId = Auth::id();
            $classScheduleId = $request->input('id');

            // Verificar que el horario existe
            $classSchedule = ClassSchedule::with(['class.discipline', 'studio', 'instructor'])
                ->find($classScheduleId);

            if (!$classSchedule) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'El horario de clase no existe',
                    'datoAdicional' => null
                ], 200);
            }

            // Buscar entradas del usuario en la lista de espera para este horario
            $waitingEntries = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('user_id', $userId)
                ->where('status', 'waiting')
                ->orderBy('created_at', 'asc')
                ->get();

            // Obtener TODA la lista de espera para este horario para calcular posición
            $allWaitingList = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('status', 'waiting')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Calcular la posición del usuario en la lista de espera
            $userPosition = null;
            $totalPeopleAhead = 0;

            if (!$waitingEntries->isEmpty()) {
                // Encontrar la primera entrada del usuario en la lista ordenada
                $userFirstEntry = $waitingEntries->first();

                // Buscar la posición de esta entrada en la lista completa ordenada
                $position = 1;
                foreach ($allWaitingList as $entry) {
                    if ($entry->id === $userFirstEntry->id) {
                        $userPosition = $position;
                        $totalPeopleAhead = $position - 1;
                        break;
                    }
                    $position++;
                }
            }

            $isInWaitingList = !$waitingEntries->isEmpty();

            $formattedData = [
                'is_in_waiting_list' => $isInWaitingList,
                'class_schedule_info' => [
                    'id' => $classSchedule->id,
                    'scheduled_date' => $classSchedule->scheduled_date,
                    'start_time' => $classSchedule->start_time,
                    'end_time' => $classSchedule->end_time,
                    'status' => $classSchedule->status,
                    'max_capacity' => $classSchedule->max_capacity,
                    'available_spots' => $classSchedule->available_spots,
                    'booked_spots' => $classSchedule->booked_spots,
                    'waitlist_spots' => $classSchedule->waitlist_spots,
                    'theme' => $classSchedule->theme ?? null
                ],
                'class' => [
                    'id' => $classSchedule->class->id,
                    'name' => $classSchedule->class->name,
                    'discipline' => $classSchedule->class->discipline->name ?? 'N/A',
                    'img_url' => $classSchedule->class->img_url ? asset('storage/') . '/' . $classSchedule->class->img_url : asset('default/class.jpg'),
                    'icon_url' => $classSchedule->class->icon_url ? asset('storage/') . '/' . $classSchedule->class->icon_url : asset('default/icon.png'),
                    'discipline_img' => $classSchedule->class->discipline->icon_url ? asset('storage/') . '/' . $classSchedule->class->discipline->icon_url : asset('default/icon.png'),
                ],
                'instructor' => [
                    'id' => $classSchedule->instructor->id,
                    'name' => $classSchedule->instructor->name,
                    'profile_image' => $classSchedule->instructor->profile_image ? asset('storage/') . '/' . $classSchedule->instructor->profile_image : null,
                    'rating_average' => $classSchedule->instructor->rating_average ?? null,
                    'is_head_coach' => $classSchedule->instructor->is_head_coach ?? false,
                ],
                'studio' => [
                    'id' => $classSchedule->studio->id,
                    'name' => $classSchedule->studio->name,
                    'location' => $classSchedule->studio->location ?? 'N/A',
                ]
            ];

            // Si está en la lista de espera, agregar información adicional
            if ($isInWaitingList) {
                $formattedData['waiting_info'] = [
                    'total_user_entries' => $waitingEntries->count(),
                    'user_entries' => $waitingEntries->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'status' => $entry->status,
                            'created_at' => $entry->created_at->toISOString(),
                            'updated_at' => $entry->updated_at->toISOString()
                        ];
                    }),
                    'position_info' => [
                        'my_position' => $userPosition,
                        'total_people_ahead' => $totalPeopleAhead,
                        'total_waiting_list' => $allWaitingList->count()
                    ]
                ];
            }

            $message = $isInWaitingList
                ? 'El usuario está en la lista de espera para este horario'
                : 'El usuario no está en la lista de espera para este horario';

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => $message,
                'datoAdicional' => $formattedData
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al verificar estado en lista de espera', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $request->input('id'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al verificar estado en lista de espera',
                'datoAdicional' => $th->getMessage(),
            ], 200);
        }
    }

    /**
     * Abandonar la lista de espera
     *
     * @return \Illuminate\Http\JsonResponse
     * **Requiere autenticación:** Incluye el token Bearer en el header Authorization.
     *
     */

    public function destroy(Request $request)
    {

        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id',
            ]);

            $userId = Auth::id();
            $classScheduleId = $request->input('class_schedule_id');

            // Buscar todas las entradas del usuario en la lista de espera para esta clase
            $waitingEntries = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('user_id', $userId)
                ->where('status', 'waiting')
                ->get();

            if ($waitingEntries->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No estás en la lista de espera para esta clase',
                    'datoAdicional' => null
                ], 200);
            }

            // Contar cuántas entradas se van a eliminar
            $entriesToDelete = $waitingEntries->count();

            // Reembolsar clases consumidas antes de eliminar
            $refundedPackages = [];
            $packageValidationService = new PackageValidationService();

            \Illuminate\Support\Facades\DB::transaction(function () use (
                $waitingEntries,
                $userId,
                $classScheduleId,
                $packageValidationService,
                &$refundedPackages
            ) {
                // Reembolsar clases de paquetes (las que tienen user_package_id)
                foreach ($waitingEntries as $entry) {
                    if ($entry->user_package_id) {
                        // Reembolsar clase al paquete
                        $refundResult = $packageValidationService->refundClassToPackage($entry->user_package_id, $userId);
                        if ($refundResult['success']) {
                            $refundedPackages[] = $refundResult['refunded_package'];
                        }
                    }
                    // Nota: Si se consumió de una membresía, no podemos reembolsar automáticamente
                    // porque no tenemos el user_membership_id guardado en la tabla
                }

                // Eliminar todas las entradas del usuario en la lista de espera para esta clase
                WaitingClass::where('class_schedules_id', $classScheduleId)
                    ->where('user_id', $userId)
                    ->where('status', 'waiting')
                    ->delete();
            });

            $deletedCount = $entriesToDelete;

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => "Has sido eliminado de la lista de espera. Se eliminaron {$deletedCount} entradas.",
                'datoAdicional' => [
                    'deleted_entries_count' => $deletedCount,
                    'class_schedule_id' => $classScheduleId,
                    'refunded_packages' => $refundedPackages,
                    'refunded_packages_count' => count($refundedPackages),
                    'note' => 'Se eliminaron todas las entradas del usuario en la lista de espera para esta clase. Las clases de paquetes han sido reembolsadas. Si se consumieron clases de membresías, no se pueden reembolsar automáticamente.'
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al eliminar de la lista de espera', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $request->input('class_schedule_id'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error interno al eliminar de la lista de espera',
                'datoAdicional' => $th->getMessage(),
            ], 200);
        }
    }
}
