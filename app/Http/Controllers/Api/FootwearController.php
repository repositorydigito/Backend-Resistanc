<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootwearReservation;
use App\Models\ClassSchedule;
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
