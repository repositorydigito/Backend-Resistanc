<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            $waitingList = WaitingClass::where('user_id', $userId)
                ->where('status', 'waiting')
                ->with(['classSchedule.class', 'classSchedule.studio'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lista de espera obtenida exitosamente',
                'data' => $waitingList
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error al obtener lista de espera', [
                'user_id' => Auth::id(),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al obtener lista de espera',
                'data' => null
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
     *     "id": 1,
     *     "class_schedules_id": 10,
     *     "user_id": 25,
     *     "status": "waiting",
     *     "created_at": "2025-06-20T20:30:00.000Z",
     *     "updated_at": "2025-06-20T20:30:00.000Z"
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

            // Verificar que el usuario no esté ya en la lista de espera para este horario
            $existingWaiting = WaitingClass::where('class_schedules_id', $classScheduleId)
                ->where('user_id', $userId)
                ->where('status', 'waiting')
                ->first();

            if ($existingWaiting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya estás en la lista de espera para este horario',
                    'data' => [
                        'reason' => 'already_in_waiting_list',
                        'waiting_id' => $existingWaiting->id
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
