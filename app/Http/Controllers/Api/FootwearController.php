<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootwearReservation;
use App\Models\ClassSchedule;
use App\Models\Footwear;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

/**
 * @tags Calzados
 */
class FootwearController extends Controller
{
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
     * Crear una reserva de calzado asociada a una clase
     */
    public function reserve(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_schedules_id' => 'required|exists:class_schedules,id',
                'tallas' => 'required|array|min:1',
                'tallas.*' => 'required',
                // 'user_client_id' => 'required|exists:users,id',
            ]);

            $classSchedule = ClassSchedule::findOrFail($validated['class_schedules_id']);

            $fechaClase = $classSchedule->scheduled_date;
            $horaInicio = $classSchedule->start_time;
            $horaFin = $classSchedule->end_time;
            $inicioClase = $fechaClase . ' ' . $horaInicio;
            $finClase = $fechaClase . ' ' . $horaFin;

            // Contar disponibilidad real (libres) e IDs por talla solicitada y recolectar faltantes
            $disponiblesPorTalla = [];
            $idsLibresPorTalla = [];
            $tallasFaltantes = [];
            foreach (array_count_values($validated['tallas']) as $talla => $cantidadSolicitada) {
                $footwears = \App\Models\Footwear::where('size', $talla)
                    ->where('status', 'available')
                    ->get();

                $libres = 0;
                $idsLibres = [];
                foreach ($footwears as $footwear) {
                    $yaReservado = \App\Models\FootwearReservation::where('footwear_id', $footwear->id)
                        ->where('class_schedules_id', $classSchedule->id)
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
                    $idsLibres[] = $footwear->id;
                }
                $disponiblesPorTalla[$talla] = $libres;
                $idsLibresPorTalla[$talla] = $idsLibres;
                if ($libres < $cantidadSolicitada) {
                    $tallasFaltantes[] = $talla;
                }
            }

            if (!empty($tallasFaltantes)) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 7,
                    'mensajeUsuario' => "No hay suficiente stock para las tallas: " . implode(', ', $tallasFaltantes) . ".",
                    'datoAdicional' => [
                        'disponibles' => $disponiblesPorTalla,
                        // 'ids_libres' => $idsLibresPorTalla,
                    ],

                ], 200);
            }

            $reservas = [];
            $calzadosReservados = [];

            foreach ($validated['tallas'] as $talla) {
                // Buscar todos los calzados disponibles de esa talla que no hayan sido ya reservados en este ciclo
                $footwears = \App\Models\Footwear::where('size', $talla)
                    ->where('status', 'available')
                    ->whereNotIn('id', $calzadosReservados)
                    ->get();

                $footwearLibre = null;

                foreach ($footwears as $footwear) {
                    $yaReservado = \App\Models\FootwearReservation::where('footwear_id', $footwear->id)
                        ->where('class_schedules_id', $classSchedule->id)
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
                    return response()->json([
                        'exito' => false,
                        'codMensaje' => 6,
                        'mensajeUsuario' => "No hay suficiente calzado disponible en la talla $talla para ese horario.",
                        'datoAdicional' => $disponiblesPorTalla,
                    ], 200);
                }

                $date = $classSchedule->scheduled_date;

                $reserva = \App\Models\FootwearReservation::create([
                    'reservation_date' => now(),
                    'scheduled_date' => $date,
                    'expiration_date' => $date,
                    'status' => 'pending',
                    'class_schedules_id' => $classSchedule->id,
                    'footwear_id' => $footwearLibre->id,
                    'user_client_id' => Auth::id(),
                    'user_id' => Auth::id(),
                ]);

                $reservas[] = $reserva;
                $calzadosReservados[] = $footwearLibre->id;
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Reservas de calzado creadas exitosamente.',
                'datoAdicional' => $reservas,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Error al agregar el calzado a la reserva.',
                'datoAdicional' => $e->getMessage(),
            ], 200);
        }
    }
}
