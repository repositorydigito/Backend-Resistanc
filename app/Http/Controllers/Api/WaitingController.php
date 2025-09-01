<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * @summary Obtener lista de espera del usuario autenticado
     * @operationId indexWaitingList
     * @tags Lista de espera
     *
     * Retorna la lista de espera del usuario actualmente autenticado.
     * Devuelve todas las entradas activas en estado `waiting`, con información relacionada a la clase y estudio.
     *
     * **Requiere autenticación:** Incluye el token Bearer en el encabezado Authorization.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Lista de espera obtenida exitosamente",
     *   "data": [
     *     {
     *       "id": 5,
     *       "class_schedules_id": 10,
     *       "user_id": 25,
     *       "status": "waiting",
     *       "created_at": "2025-06-20T20:30:00.000Z",
     *       "updated_at": "2025-06-20T20:30:00.000Z",
     *       "class_schedule": {
     *         "id": 10,
     *         "scheduled_date": "2025-06-28",
     *         "start_time": "09:00:00",
     *         "class": {
     *           "id": 3,
     *           "name": "Yoga Matutino"
     *         },
     *         "studio": {
     *           "id": 2,
     *           "name": "Studio A"
     *         }
     *       }
     *     }
     *   ]
     * }
     *
     * @response 200 {
     *   "success": false,
     *   "message": "Error interno al obtener lista de espera",
     *   "data": null
     * }
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
                        'waitlist_spots' => $schedule->waitlist_spots
                    ],
                    'class' => [
                        'id' => $schedule->class->id,
                        'name' => $schedule->class->name,
                        'discipline' => $schedule->class->discipline->name ?? 'N/A',
                        'img_url' => $schedule->class->img_url ? asset('storage/') . '/' . $schedule->class->img_url : null,
                        'discipline_img' => $schedule->class->discipline->icon_url ? asset('storage/') . '/' . $schedule->class->discipline->icon_url : null,
                    ],
                    'instructor' => [
                        'id' => $schedule->instructor->id,
                        'name' => $schedule->instructor->name,
                        'profile_image' => $schedule->instructor->profile_image ? asset('storage/') . '/' . $schedule->instructor->profile_image : null,
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
     * @summary Agregar usuario a la lista de espera
     * @operationId addWaitingList
     * @tags Lista de espera
     *
     * Agrega un usuario a la lista de espera de una clase programada.
     * **IMPORTANTE:** El usuario debe tener paquetes disponibles para la disciplina de la clase.
     * El sistema validará automáticamente que el usuario tenga paquetes activos y con clases
     * disponibles para la disciplina específica antes de permitir que se agregue a la lista de espera.
     * **NOTA:** Los paquetes NO se consumen al agregarse a la lista de espera, solo se validan.
     * **MÚLTIPLES ENTRADAS:** Un usuario puede estar múltiples veces en la lista de espera,
     * limitado por la cantidad de asientos disponibles en sus paquetes.
     *
     * **Requiere autenticación:** Incluye el token Bearer en el encabezado Authorization.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @requestBody required {
     *   "class_schedule_id": 10
     * }
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Usuario agregado a la lista de espera exitosamente",
     *   "data": {
     *     "waiting_list": {
     *       "id": 1,
     *       "class_schedules_id": 10,
     *       "user_id": 25,
     *       "status": "waiting",
     *       "created_at": "2025-06-20T20:30:00.000Z",
     *       "updated_at": "2025-06-20T20:30:00.000Z"
     *     },
     *     "waiting_summary": {
     *       "total_waiting_entries": 3,
     *       "max_allowed_entries": 15,
     *       "remaining_entries": 12
     *     },
     *     "package_validation": {
     *       "discipline_required": {
     *         "id": 1,
     *         "name": "Yoga"
     *       },
     *       "available_packages_count": 2,
     *       "note": "Los paquetes no se consumen hasta que reserves un asiento"
     *     }
     *   }
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
     *   "message": "Ya tienes el máximo de entradas en la lista de espera para este horario",
     *   "data": {
     *     "reason": "max_waiting_entries_reached",
     *     "current_waiting_count": 15,
     *     "max_allowed": 15,
     *     "available_packages_count": 2
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "class_schedule_id": ["The class schedule id field is required."]
     *   }
     * }
     */

    public function addWaitingList(Request $request)
    {
        try {
            // Validar datos de entrada
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id',
            ]);

            $userId = Auth::id();
            $classScheduleId = $request->input('class_schedule_id');

            // Obtener el horario de clase con sus relaciones
            $classSchedule = ClassSchedule::with(['class.discipline'])->findOrFail($classScheduleId);

            // Verificar que el horario está activo
            if ($classSchedule->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede agregar a la lista de espera de un horario cancelado',
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
                    'message' => 'No se puede agregar a la lista de espera de un horario pasado',
                    'data' => ['reason' => 'schedule_past']
                ], 200);
            }

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

            // Verificar cuántas veces el usuario ya está en la lista de espera para este horario
            $existingWaitingCount = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('user_id', $userId)
                ->where('status', 'waiting')
                ->count();

            // Obtener la cantidad total de asientos disponibles en los paquetes del usuario
            $disciplineId = $classSchedule->class->discipline_id;
            $availablePackages = $packageValidationService->getUserAvailablePackagesForDiscipline($userId, $disciplineId);
            $totalAvailableSeats = $availablePackages->sum('remaining_classes');

            // Limitar la cantidad de entradas en lista de espera al número de asientos disponibles en paquetes
            if ($existingWaitingCount >= $totalAvailableSeats) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes el máximo de entradas en la lista de espera para este horario',
                    'data' => [
                        'reason' => 'max_waiting_entries_reached',
                        'current_waiting_count' => $existingWaitingCount,
                        'max_allowed' => $totalAvailableSeats,
                        'available_packages_count' => count($packageValidation['available_packages'])
                    ]
                ], 200);
            }

            // Log de validación exitosa para debugging
            Log::info('Usuario agregado a lista de espera - paquetes validados', [
                'user_id' => $userId,
                'schedule_id' => $classScheduleId,
                'discipline_required' => $packageValidation['discipline_required'],
                'available_packages_count' => count($packageValidation['available_packages'])
            ]);

            // Agregar a la lista de espera
            $waitingList = WaitingClass::create([
                'class_schedules_id' => $classScheduleId,
                'user_id' => $userId,
                'status' => 'waiting',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario agregado a la lista de espera exitosamente',
                'data' => [
                    'waiting_list' => $waitingList,
                    'waiting_summary' => [
                        'total_waiting_entries' => $existingWaitingCount + 1,
                        'max_allowed_entries' => $totalAvailableSeats,
                        'remaining_entries' => $totalAvailableSeats - ($existingWaitingCount + 1)
                    ],
                    'package_validation' => [
                        'discipline_required' => $packageValidation['discipline_required'],
                        'available_packages_count' => count($packageValidation['available_packages']),
                        'note' => 'Los paquetes no se consumen hasta que reserves un asiento'
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
                'success' => false,
                'message' => 'Error interno al agregar a la lista de espera',
                'data' => null
            ], 200);
        }
    }
}
