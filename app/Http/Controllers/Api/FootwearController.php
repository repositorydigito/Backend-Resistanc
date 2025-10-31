<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootwearReservation;
use App\Models\ClassSchedule;
use App\Models\Footwear;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * @tags Calzados
 */
class FootwearController extends Controller
{
    /**
     * Obtener disponibilidad de calzados para un horario específico
     */
    public function getAvailabilityForSchedule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id'
            ]);

            $classScheduleId = $request->integer('class_schedule_id');

            // Obtener el horario solicitado
            $classSchedule = \App\Models\ClassSchedule::findOrFail($classScheduleId);

            // Obtener fecha y hora del horario
            $scheduledDate = $classSchedule->scheduled_date instanceof \Carbon\Carbon
                ? $classSchedule->scheduled_date->format('Y-m-d')
                : $classSchedule->scheduled_date;
            $startTime = $classSchedule->start_time;
            $endTime = $classSchedule->end_time;

            // Buscar horarios que se solapen en la misma fecha y hora
            $overlappingSchedules = \App\Models\ClassSchedule::where('scheduled_date', $scheduledDate)
                ->where('id', '!=', $classScheduleId)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                    });
                })
                ->pluck('id')
                ->toArray();

            // Agregar el horario actual
            $overlappingSchedules[] = $classScheduleId;

            // Obtener todas las reservas de calzado para horarios solapados
            $reservedFootwearIds = \App\Models\FootwearReservation::whereIn('class_schedules_id', $overlappingSchedules)
                ->whereIn('status', ['pending', 'confirmed'])
                ->pluck('footwear_id')
                ->toArray();

            // Obtener todos los calzados agrupados por talla
            $footwearsBySize = \App\Models\Footwear::select('size', DB::raw('count(*) as total_count'))
                ->where('status', 'available')
                ->groupBy('size')
                ->orderBy('size')
                ->get();

            // Calcular disponibilidad por talla
            $availability = $footwearsBySize->map(function ($sizeGroup) use ($reservedFootwearIds) {
                $size = $sizeGroup->size;
                $totalCount = $sizeGroup->total_count;

                // Contar cuántos de esta talla están reservados en horarios solapados
                $reservedCount = \App\Models\Footwear::whereIn('id', $reservedFootwearIds)
                    ->where('size', $size)
                    ->where('status', 'available')
                    ->count();

                $availableCount = $totalCount - $reservedCount;

                return [
                    'size' => $size,
                    'total_count' => $totalCount,
                    'reserved_count' => $reservedCount,
                    'available_count' => max(0, $availableCount),
                    'is_available' => $availableCount > 0,
                    'status' => $availableCount > 0 ? 'available' : 'unavailable'
                ];
            });

            // Información del horario
            $scheduleInfo = [
                'class_schedule_id' => $classSchedule->id,
                'class_name' => $classSchedule->class->name ?? 'N/A',
                'scheduled_date' => $scheduledDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'has_overlapping_schedules' => count($overlappingSchedules) > 1,
                'overlapping_schedules_count' => count($overlappingSchedules) - 1
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Disponibilidad de calzados obtenida exitosamente',
                'datoAdicional' => [
                    'schedule_info' => $scheduleInfo,
                    'footwear_availability' => $availability,
                    'summary' => [
                        'total_sizes_available' => $availability->where('is_available', true)->count(),
                        'total_sizes' => $availability->count(),
                        'total_footwears_available' => $availability->sum('available_count'),
                        'total_footwears_reserved' => $availability->sum('reserved_count')
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener disponibilidad de calzados',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener todos los calzados disponibles con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'size' => 'sometimes|string',
                'status' => 'sometimes|string|in:available,unavailable,maintenance',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1'
            ]);

            $query = Footwear::query();

            // Filtros opcionales
            if ($request->filled('size')) {
                $query->where('size', $request->size);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {
                // Por defecto mostrar solo disponibles
                $query->where('status', 'available');
            }

            // Ordenar por talla y luego por ID
            $query->orderBy('size')->orderBy('id');

            // Paginación opcional
            if ($request->has('per_page')) {
                $footwears = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Calzados obtenidos exitosamente',
                    'datoAdicional' => [
                        'footwears' => $footwears->items(),
                        'pagination' => [
                            'current_page' => $footwears->currentPage(),
                            'last_page' => $footwears->lastPage(),
                            'per_page' => $footwears->perPage(),
                            'total' => $footwears->total(),
                            'from' => $footwears->firstItem(),
                            'to' => $footwears->lastItem(),
                            'has_more_pages' => $footwears->hasMorePages(),
                        ]
                    ]
                ], 200);
            } else {
                // Sin paginación - retornar todos los resultados
                $footwears = $query->get();

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Calzados obtenidos exitosamente',
                    'datoAdicional' => [
                        'footwears' => $footwears,
                        'total_count' => $footwears->count(),
                        'summary' => [
                            'by_size' => $footwears->groupBy('size')->map->count(),
                            'by_status' => $footwears->groupBy('status')->map->count()
                        ]
                    ]
                ], 200);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los calzados',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener calzados disponibles para un horario específico
     */
    public function indexClassSchedule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id',
                'size' => 'sometimes|string'
            ]);

            $classSchedule = ClassSchedule::findOrFail($request->class_schedule_id);

            $fechaClase = $classSchedule->scheduled_date;
            $horaInicio = $classSchedule->start_time;
            $horaFin = $classSchedule->end_time;
            $inicioClase = $fechaClase . ' ' . $horaInicio;
            $finClase = $fechaClase . ' ' . $horaFin;

            // Obtener todos los calzados disponibles
            $query = Footwear::where('status', 'available');

            if ($request->filled('size')) {
                $query->where('size', $request->size);
            }

            $footwears = $query->get();

            $availableFootwears = [];
            $unavailableFootwears = [];

            foreach ($footwears as $footwear) {
                $isAvailable = true;
                $unavailabilityReason = null;

                // Verificar si ya está reservado para esta clase
                $yaReservado = FootwearReservation::where('footwear_id', $footwear->id)
                    ->where('class_schedules_id', $classSchedule->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($yaReservado) {
                    $isAvailable = false;
                    $unavailabilityReason = 'reserved_for_class';
                } else {
                    // Verificar si está en uso durante el horario de la clase
                    $enUso = \App\Models\FootwearLoan::where('footwear_id', $footwear->id)
                        ->where('status', 'in_use')
                        ->where(function ($query) use ($inicioClase, $finClase) {
                            $query->where('loan_date', '<', $finClase)
                                ->where(function ($q2) use ($inicioClase) {
                                    $q2->whereNull('return_date')
                                        ->orWhere('return_date', '>', $inicioClase);
                                });
                        })
                        ->exists();

                    if ($enUso) {
                        $isAvailable = false;
                        $unavailabilityReason = 'in_use';
                    }
                }

                $footwearData = [
                    'id' => $footwear->id,
                    'size' => $footwear->size,
                    'brand' => $footwear->brand,
                    'model' => $footwear->model,
                    'color' => $footwear->color,
                    'status' => $footwear->status,
                    'is_available_for_class' => $isAvailable,
                    'unavailability_reason' => $unavailabilityReason
                ];

                if ($isAvailable) {
                    $availableFootwears[] = $footwearData;
                } else {
                    $unavailableFootwears[] = $footwearData;
                }
            }

            // Agrupar por talla
            $availableBySize = collect($availableFootwears)->groupBy('size')->map->count();
            $unavailableBySize = collect($unavailableFootwears)->groupBy('size')->map->count();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Calzados para el horario obtenidos exitosamente',
                'datoAdicional' => [
                    'class_schedule_info' => [
                        'id' => $classSchedule->id,
                        'class_name' => $classSchedule->class->name ?? 'N/A',
                        'instructor_name' => $classSchedule->instructor->name ?? 'N/A',
                        'studio_name' => $classSchedule->studio->name ?? 'N/A',
                        'scheduled_date' => $classSchedule->scheduled_date,
                        'start_time' => $classSchedule->start_time,
                        'end_time' => $classSchedule->end_time
                    ],
                    'available_footwears' => $availableFootwears,
                    'unavailable_footwears' => $unavailableFootwears,
                    'summary' => [
                        'total_available' => count($availableFootwears),
                        'total_unavailable' => count($unavailableFootwears),
                        'available_by_size' => $availableBySize,
                        'unavailable_by_size' => $unavailableBySize
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener los calzados para el horario',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener los calzados que el usuario ha reservado en una clase específica
     */
    public function getMyReservationsForSchedule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_schedule_id' => 'required|integer|exists:class_schedules,id'
            ]);

            $userId = Auth::id();
            $classScheduleId = $request->integer('class_schedule_id');

            // Obtener el horario
            $classSchedule = ClassSchedule::with(['class', 'instructor', 'studio'])
                ->findOrFail($classScheduleId);

            // Obtener todas las reservas del usuario en este horario
            $reservations = FootwearReservation::with(['footwear'])
                ->where('class_schedules_id', $classScheduleId)
                ->where('user_client_id', $userId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            if ($reservations->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes calzados reservados en esta clase',
                    'datoAdicional' => [
                        'class_schedule_info' => [
                            'id' => $classSchedule->id,
                            'class_name' => $classSchedule->class->name ?? 'N/A',
                            'scheduled_date' => $classSchedule->scheduled_date,
                            'start_time' => $classSchedule->start_time,
                            'end_time' => $classSchedule->end_time
                        ],
                        'reservations' => []
                    ]
                ], 200);
            }

            // Formatear reservas
            $formattedReservations = $reservations->map(function ($reservation) {
                return [
                    'reservation_id' => $reservation->id,
                    'footwear_id' => $reservation->footwear_id,
                    'footwear_code' => $reservation->footwear->code ?? 'N/A',
                    'footwear_model' => $reservation->footwear->model ?? 'N/A',
                    'footwear_brand' => $reservation->footwear->brand ?? 'N/A',
                    'size' => $reservation->footwear->size ?? null,
                    'color' => $reservation->footwear->color ?? 'N/A',
                    'type' => $reservation->footwear->type ?? 'N/A',
                    'gender' => $reservation->footwear->gender ?? 'N/A',
                    'image' => $reservation->footwear->image ? asset('storage/' . $reservation->footwear->image) : null,
                    'reservation_status' => $reservation->status,
                    'reservation_date' => $reservation->reservation_date,
                    'scheduled_date' => $reservation->scheduled_date,
                ];
            });

            // Agrupar por talla
            $reservationsBySize = $formattedReservations->groupBy('size')->map(function ($items) {
                return [
                    'size' => $items->first()['size'],
                    'quantity' => $items->count(),
                    'footwears' => $items->values()
                ];
            })->values();

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Calzados reservados obtenidos exitosamente',
                'datoAdicional' => [
                    'class_schedule_info' => [
                        'id' => $classSchedule->id,
                        'class_name' => $classSchedule->class->name ?? 'N/A',
                        'instructor_name' => $classSchedule->instructor->name ?? 'N/A',
                        'studio_name' => $classSchedule->studio->name ?? 'N/A',
                        'scheduled_date' => $classSchedule->scheduled_date,
                        'start_time' => $classSchedule->start_time,
                        'end_time' => $classSchedule->end_time
                    ],
                    'reservations' => $formattedReservations,
                    'reservations_by_size' => $reservationsBySize,
                    'summary' => [
                        'total_reservations' => $reservations->count(),
                        'total_sizes' => $reservationsBySize->count(),
                        'sizes_reserved' => $formattedReservations->pluck('size')->unique()->values()
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener calzados reservados',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Editar/Actualizar las reservas de calzado de una clase específica
     */
    public function updateReservation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_schedules_id' => 'required|exists:class_schedules,id',
                'footwear_sizes' => 'required|array|min:1',
                'footwear_sizes.*.size' => 'required|integer|min:1',
                'footwear_sizes.*.quantity' => 'required|integer|min:1|max:10',
            ]);

            $userId = Auth::id();
            $classScheduleId = $validated['class_schedules_id'];
            $classSchedule = ClassSchedule::findOrFail($classScheduleId);

            $fechaClase = $classSchedule->scheduled_date;
            $horaInicio = $classSchedule->start_time;
            $horaFin = $classSchedule->end_time;
            $inicioClase = $fechaClase . ' ' . $horaInicio;
            $finClase = $fechaClase . ' ' . $horaFin;

            // Agrupar tallas y cantidades solicitadas
            $tallasConCantidad = [];
            foreach ($validated['footwear_sizes'] as $item) {
                $talla = $item['size'];
                $cantidad = $item['quantity'];

                if (!isset($tallasConCantidad[$talla])) {
                    $tallasConCantidad[$talla] = 0;
                }
                $tallasConCantidad[$talla] += $cantidad;
            }

            // Obtener reservas actuales del usuario en este horario
            $reservasActuales = FootwearReservation::where('class_schedules_id', $classScheduleId)
                ->where('user_client_id', $userId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            $calzadosReservadosActualmente = $reservasActuales->pluck('footwear_id')->toArray();

            // Validar disponibilidad excluyendo las reservas actuales del usuario
            $disponiblesPorTalla = [];
            $tallasFaltantes = [];

            foreach ($tallasConCantidad as $talla => $cantidadSolicitada) {
                $footwears = Footwear::where('size', $talla)
                    ->where('status', 'available')
                    ->get();

                $libres = 0;
                foreach ($footwears as $footwear) {
                    // Si el calzado ya está reservado por este usuario, contarlo como disponible
                    if (in_array($footwear->id, $calzadosReservadosActualmente)) {
                        $libres++;
                        continue;
                    }

                    $yaReservado = FootwearReservation::where('footwear_id', $footwear->id)
                        ->where('class_schedules_id', $classScheduleId)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->exists();
                    if ($yaReservado) continue;

                    $enUso = \App\Models\FootwearLoan::where('footwear_id', $footwear->id)
                        ->where('status', 'in_use')
                        ->where(function ($query) use ($inicioClase, $finClase) {
                            $query->where('loan_date', '<', $finClase)
                                ->where(function ($q2) use ($inicioClase) {
                                    $q2->whereNull('return_date')
                                        ->orWhere('return_date', '>', $inicioClase);
                                });
                        })
                        ->exists();
                    if ($enUso) continue;

                    $libres++;
                }

                $disponiblesPorTalla[$talla] = $libres;
                if ($libres < $cantidadSolicitada) {
                    $tallasFaltantes[] = $talla;
                }
            }

            if (!empty($tallasFaltantes)) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => "No hay suficiente stock para las tallas: " . implode(', ', $tallasFaltantes) . ".",
                    'datoAdicional' => [
                        'sizes_requested' => $tallasConCantidad,
                        'sizes_available' => $disponiblesPorTalla,
                        'sizes_missing' => $tallasFaltantes,
                    ],
                ], 200);
            }

            // Usar transacción para asegurar consistencia
            DB::beginTransaction();
            try {
                // Cancelar todas las reservas actuales del usuario en este horario
                FootwearReservation::where('class_schedules_id', $classScheduleId)
                    ->where('user_client_id', $userId)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->update(['status' => 'canceled']);

                // Crear las nuevas reservas
                $reservas = [];
                $calzadosReservados = [];

                foreach ($tallasConCantidad as $talla => $cantidadSolicitada) {
                    for ($i = 0; $i < $cantidadSolicitada; $i++) {
                        $footwears = Footwear::where('size', $talla)
                            ->where('status', 'available')
                            ->whereNotIn('id', $calzadosReservados)
                            ->get();

                        $footwearLibre = null;

                        foreach ($footwears as $footwear) {
                            $yaReservado = FootwearReservation::where('footwear_id', $footwear->id)
                                ->where('class_schedules_id', $classScheduleId)
                                ->whereIn('status', ['pending', 'confirmed'])
                                ->exists();

                            if ($yaReservado) continue;

                            $enUso = \App\Models\FootwearLoan::where('footwear_id', $footwear->id)
                                ->where('status', 'in_use')
                                ->where(function ($query) use ($inicioClase, $finClase) {
                                    $query->where('loan_date', '<', $finClase)
                                        ->where(function ($q2) use ($inicioClase) {
                                            $q2->whereNull('return_date')
                                                ->orWhere('return_date', '>', $inicioClase);
                                        });
                                })
                                ->exists();

                            if ($enUso) continue;

                            $footwearLibre = $footwear;
                            break;
                        }

                        if (!$footwearLibre) {
                            DB::rollBack();
                            return response()->json([
                                'exito' => false,
                                'codMensaje' => 0,
                                'mensajeUsuario' => "No hay suficiente calzado disponible en la talla $talla para ese horario.",
                                'datoAdicional' => [
                                    'size_requested' => $talla,
                                    'sizes_available' => $disponiblesPorTalla,
                                ],
                            ], 200);
                        }

                        $reserva = FootwearReservation::create([
                            'reservation_date' => now(),
                            'scheduled_date' => $fechaClase,
                            'expiration_date' => $fechaClase,
                            'status' => 'pending',
                            'class_schedules_id' => $classScheduleId,
                            'footwear_id' => $footwearLibre->id,
                            'user_client_id' => $userId,
                            'user_id' => $userId,
                        ]);

                        $reservas[] = $reserva;
                        $calzadosReservados[] = $footwearLibre->id;
                    }
                }

                DB::commit();

                // Agrupar reservas por talla para el resumen
                $reservasPorTalla = [];
                foreach ($reservas as $reserva) {
                    $footwear = Footwear::find($reserva->footwear_id);
                    if ($footwear) {
                        $talla = $footwear->size;
                        if (!isset($reservasPorTalla[$talla])) {
                            $reservasPorTalla[$talla] = 0;
                        }
                        $reservasPorTalla[$talla]++;
                    }
                }

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 1,
                    'mensajeUsuario' => 'Reservas actualizadas exitosamente.',
                    'datoAdicional' => [
                        'reservations' => $reservas,
                        'previous_reservations_canceled' => $reservasActuales->count(),
                        'summary' => [
                            'total_reservations' => count($reservas),
                            'sizes_reserved' => $reservasPorTalla,
                            'class_schedule_info' => [
                                'id' => $classSchedule->id,
                                'class_name' => $classSchedule->class->name ?? 'N/A',
                                'scheduled_date' => $classSchedule->scheduled_date,
                                'start_time' => $classSchedule->start_time,
                                'end_time' => $classSchedule->end_time
                            ]
                        ]
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al actualizar las reservas de calzado',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Crear una reserva de calzado asociada a una clase
     */
    public function reserve(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_schedules_id' => 'required|exists:class_schedules,id',
                'footwear_sizes' => 'required|array|min:1',
                'footwear_sizes.*.size' => 'required|integer|min:1',
                'footwear_sizes.*.quantity' => 'required|integer|min:1|max:10',
            ]);

            $userId = Auth::id();
            $classSchedule = ClassSchedule::findOrFail($validated['class_schedules_id']);

            $fechaClase = $classSchedule->scheduled_date;
            $horaInicio = $classSchedule->start_time;
            $horaFin = $classSchedule->end_time;
            $inicioClase = $fechaClase . ' ' . $horaInicio;
            $finClase = $fechaClase . ' ' . $horaFin;

            // Agrupar tallas y cantidades solicitadas
            $tallasConCantidad = [];
            foreach ($validated['footwear_sizes'] as $item) {
                $talla = $item['size'];
                $cantidad = $item['quantity'];

                if (!isset($tallasConCantidad[$talla])) {
                    $tallasConCantidad[$talla] = 0;
                }
                $tallasConCantidad[$talla] += $cantidad;
            }

            // 🎯 Usar transacción para prevenir condiciones de carrera
            return DB::transaction(function () use ($classSchedule, $userId, $tallasConCantidad, $inicioClase, $finClase, $fechaClase) {
                // Validar disponibilidad DENTRO de la transacción con locks
                $disponiblesPorTalla = [];
                $tallasFaltantes = [];

                foreach ($tallasConCantidad as $talla => $cantidadSolicitada) {
                    // 🔒 Bloquear filas durante la verificación para prevenir race conditions
                    $footwears = \App\Models\Footwear::where('size', $talla)
                        ->where('status', 'available')
                        ->lockForUpdate() // Bloquear filas durante la transacción
                        ->get();

                    $libres = 0;
                    foreach ($footwears as $footwear) {
                        // Verificar si ya está reservado (dentro de la transacción)
                        $yaReservado = \App\Models\FootwearReservation::where('footwear_id', $footwear->id)
                            ->where('class_schedules_id', $classSchedule->id)
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->lockForUpdate() // Bloquear consulta de reservas también
                            ->exists();

                        if ($yaReservado) continue;

                        // Verificar si está en uso
                        $enUso = \App\Models\FootwearLoan::where('footwear_id', $footwear->id)
                            ->where('status', 'in_use')
                            ->where(function ($query) use ($inicioClase, $finClase) {
                                $query->where('loan_date', '<', $finClase)
                                    ->where(function ($q2) use ($inicioClase) {
                                        $q2->whereNull('return_date')
                                            ->orWhere('return_date', '>', $inicioClase);
                                    });
                            })
                            ->lockForUpdate() // Bloquear consulta de préstamos también
                            ->exists();

                        if ($enUso) continue;

                        $libres++;
                    }

                    $disponiblesPorTalla[$talla] = $libres;
                    if ($libres < $cantidadSolicitada) {
                        $tallasFaltantes[] = $talla;
                    }
                }

                // Si no hay suficiente disponibilidad, retornar error
                if (!empty($tallasFaltantes)) {
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 0,
                        'mensajeUsuario' => "No hay suficiente stock para las tallas: " . implode(', ', $tallasFaltantes) . ".",
                        'datoAdicional' => [
                            'sizes_requested' => $tallasConCantidad,
                            'sizes_available' => $disponiblesPorTalla,
                            'sizes_missing' => $tallasFaltantes,
                        ],
                    ], 200);
                }

                // Crear reservas por cada talla y cantidad solicitada
                $reservas = [];
                $calzadosReservados = [];

                foreach ($tallasConCantidad as $talla => $cantidadSolicitada) {
                    for ($i = 0; $i < $cantidadSolicitada; $i++) {
                        // 🔒 Buscar calzados disponibles con lock dentro de la transacción
                        $footwears = \App\Models\Footwear::where('size', $talla)
                            ->where('status', 'available')
                            ->whereNotIn('id', $calzadosReservados)
                            ->lockForUpdate() // Bloquear filas
                            ->get();

                        $footwearLibre = null;

                        foreach ($footwears as $footwear) {
                            // 🔒 Verificar disponibilidad dentro de la transacción con lock
                            $yaReservado = \App\Models\FootwearReservation::where('footwear_id', $footwear->id)
                                ->where('class_schedules_id', $classSchedule->id)
                                ->whereIn('status', ['pending', 'confirmed'])
                                ->lockForUpdate()
                                ->exists();

                            if ($yaReservado) continue;

                            $enUso = \App\Models\FootwearLoan::where('footwear_id', $footwear->id)
                                ->where('status', 'in_use')
                                ->where(function ($query) use ($inicioClase, $finClase) {
                                    $query->where('loan_date', '<', $finClase)
                                        ->where(function ($q2) use ($inicioClase) {
                                            $q2->whereNull('return_date')
                                                ->orWhere('return_date', '>', $inicioClase);
                                        });
                                })
                                ->lockForUpdate()
                                ->exists();

                            if ($enUso) continue;

                            $footwearLibre = $footwear;
                            break;
                        }

                        // Si no se encontró calzado disponible, hacer rollback
                        if (!$footwearLibre) {
                            DB::rollBack();
                            return response()->json([
                                'exito' => false,
                                'codMensaje' => 0,
                                'mensajeUsuario' => "No hay suficiente calzado disponible en la talla $talla para ese horario.",
                                'datoAdicional' => [
                                    'size_requested' => $talla,
                                    'sizes_available' => $disponiblesPorTalla,
                                    'note' => 'El calzado pudo haber sido reservado por otro usuario al mismo tiempo'
                                ],
                            ], 200);
                        }

                        // Crear la reserva dentro de la transacción
                        $reserva = \App\Models\FootwearReservation::create([
                            'reservation_date' => now(),
                            'scheduled_date' => $fechaClase,
                            'expiration_date' => $fechaClase,
                            'status' => 'pending',
                            'class_schedules_id' => $classSchedule->id,
                            'footwear_id' => $footwearLibre->id,
                            'user_client_id' => $userId,
                            'user_id' => $userId,
                        ]);

                        $reservas[] = $reserva;
                        $calzadosReservados[] = $footwearLibre->id;
                    }
                }

                // Si todo fue exitoso, la transacción se commitea automáticamente
                return $this->formatReservationResponse($reservas, $classSchedule);
            });

        } catch (\Throwable $e) {
            // Si hay un error, hacer rollback si estamos en una transacción
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Error al crear reserva de calzado', [
                'user_id' => Auth::id(),
                'class_schedule_id' => $validated['class_schedules_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al agregar el calzado a la reserva.',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Formatear respuesta de reservas (método auxiliar)
     */
    private function formatReservationResponse(array $reservas, ClassSchedule $classSchedule): JsonResponse
    {
        // Agrupar reservas por talla para el resumen
        $reservasPorTalla = [];
        foreach ($reservas as $reserva) {
            $footwear = \App\Models\Footwear::find($reserva->footwear_id);
            if ($footwear) {
                $talla = $footwear->size;
                if (!isset($reservasPorTalla[$talla])) {
                    $reservasPorTalla[$talla] = 0;
                }
                $reservasPorTalla[$talla]++;
            }
        }

        return response()->json([
            'exito' => true,
            'codMensaje' => 1,
            'mensajeUsuario' => 'Reservas de calzado creadas exitosamente.',
            'datoAdicional' => [
                'reservations' => $reservas,
                'summary' => [
                    'total_reservations' => count($reservas),
                    'sizes_reserved' => $reservasPorTalla,
                    'class_schedule_info' => [
                        'id' => $classSchedule->id,
                        'class_name' => $classSchedule->class->name ?? 'N/A',
                        'scheduled_date' => $classSchedule->scheduled_date,
                        'start_time' => $classSchedule->start_time,
                        'end_time' => $classSchedule->end_time
                    ]
                ]
            ]
        ], 200);
    }

    /**
     * Cancelar reservas de calzado para una clase específica
     */
    public function cancelReservation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_schedules_id' => 'required|exists:class_schedules,id'
            ]);

            $userId = Auth::id();
            $classScheduleId = $validated['class_schedules_id'];

            // Obtener el horario
            $classSchedule = ClassSchedule::with(['class', 'instructor', 'studio'])
                ->findOrFail($classScheduleId);

            // Obtener todas las reservas del usuario en este horario
            $reservations = FootwearReservation::where('class_schedules_id', $classScheduleId)
                ->where('user_client_id', $userId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->get();

            if ($reservations->isEmpty()) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes reservas de calzado en esta clase',
                    'datoAdicional' => [
                        'class_schedule_info' => [
                            'id' => $classSchedule->id,
                            'class_name' => $classSchedule->class->name ?? 'N/A',
                            'scheduled_date' => $classSchedule->scheduled_date,
                            'start_time' => $classSchedule->start_time,
                            'end_time' => $classSchedule->end_time
                        ],
                        'reservations_canceled' => 0
                    ]
                ], 200);
            }

            // Obtener IDs de reservas a cancelar para el resumen
            $reservationIds = $reservations->pluck('id')->toArray();

            // Cancelar todas las reservas
            $canceledCount = FootwearReservation::whereIn('id', $reservationIds)
                ->where('user_client_id', $userId)
                ->update(['status' => 'canceled']);

            // Obtener información de las reservas antes de cancelarlas para el resumen
            $canceledReservations = $reservations;

            $canceledBySize = [];
            foreach ($canceledReservations as $reservation) {
                $footwear = Footwear::find($reservation->footwear_id);
                if ($footwear && $footwear->size) {
                    $size = $footwear->size;
                    if (!isset($canceledBySize[$size])) {
                        $canceledBySize[$size] = 0;
                    }
                    $canceledBySize[$size]++;
                }
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Reservas de calzado canceladas exitosamente',
                'datoAdicional' => [
                    'class_schedule_info' => [
                        'id' => $classSchedule->id,
                        'class_name' => $classSchedule->class->name ?? 'N/A',
                        'scheduled_date' => $classSchedule->scheduled_date,
                        'start_time' => $classSchedule->start_time,
                        'end_time' => $classSchedule->end_time
                    ],
                    'reservations_canceled' => $canceledCount,
                    'summary' => [
                        'total_reservations_canceled' => $canceledCount,
                        'sizes_canceled' => $canceledBySize
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al cancelar las reservas de calzado',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
