<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    /**
     * Obtener historial de clases completadas del usuario
     */
    public function getClassHistory(): JsonResponse
    {
        try {
            $user = Auth::user();

            // 1. Obtener clases completadas del usuario (status = 'Completed')
            $completedClasses = \App\Models\ClassScheduleSeat::with([
                'classSchedule.class.discipline',
                'seat',
                'userPackage'
            ])
                ->where('user_id', $user->id)
                ->where('status', 'Completed')
                ->get();

            // 2. Calcular total de clases completadas
            $totalCompletedClasses = $completedClasses->count();

            // 3. Obtener TODOS los grupos de disciplinas del sistema
            $allGroupsData = $this->getAllDisciplineGroups();

            // 4. Agrupar clases completadas por grupos de disciplinas
            $completedByGroupsData = [];

            foreach ($completedClasses as $classScheduleSeat) {
                $classSchedule = $classScheduleSeat->classSchedule;

                if (!$classSchedule || !$classSchedule->class) {
                    continue;
                }

                // Use discipline (singular) and convert to collection
                $discipline = $classSchedule->class->discipline;
                if (!$discipline) {
                    continue;
                }

                // Create collection with single discipline
                $disciplines = collect([$discipline]);

                $disciplineIds = $disciplines->pluck('id')->sort()->values()->toArray();
                $groupKey = implode('-', $disciplineIds);

                if (!isset($completedByGroupsData[$groupKey])) {
                    $completedByGroupsData[$groupKey] = [
                        'group_key' => $groupKey,
                        'completed_count' => 0,
                        'classes' => [],
                    ];
                }

                $completedByGroupsData[$groupKey]['completed_count']++;

                // Agregar detalles de la clase completada
                $completedByGroupsData[$groupKey]['classes'][] = [
                    'id' => $classSchedule->id,
                    'class_name' => $classSchedule->class->name,
                    'scheduled_date' => $classSchedule->scheduled_date->format('Y-m-d'),
                    'start_time' => $classSchedule->start_time,
                    'end_time' => $classSchedule->end_time,
                    'completed_at' => $classSchedule->updated_at->format('Y-m-d H:i:s'),
                    'seat_code' => $classScheduleSeat->code ?? null,
                    'attendance_notes' => $classScheduleSeat->attendance_notes ?? null,
                ];
            }

            // 5. Combinar todos los grupos con los completados
            foreach ($allGroupsData as $groupKey => $group) {
                // Inicializar contador por disciplina dentro del grupo
                foreach ($group['disciplines'] as $index => $discipline) {
                    $allGroupsData[$groupKey]['disciplines'][$index]['completed_count_in_group'] = 0;
                }

                // Si este grupo tiene clases completadas, asignarlas
                if (isset($completedByGroupsData[$groupKey])) {
                    $allGroupsData[$groupKey]['completed_count'] = $completedByGroupsData[$groupKey]['completed_count'];
                    $allGroupsData[$groupKey]['classes'] = $completedByGroupsData[$groupKey]['classes'];
                } else {
                    $allGroupsData[$groupKey]['completed_count'] = 0;
                    $allGroupsData[$groupKey]['classes'] = [];
                }
            }

            // 6. Contar clases completadas por cada disciplina dentro de cada grupo
            foreach ($completedClasses as $classScheduleSeat) {
                $classSchedule = $classScheduleSeat->classSchedule;

                if (!$classSchedule || !$classSchedule->class || !$classSchedule->class->discipline) {
                    continue;
                }

                // Use discipline (singular) and convert to collection
                $discipline = $classSchedule->class->discipline;
                $completedDisciplineId = $discipline->id;

                // Buscar en TODOS los grupos si esta disciplina está presente
                foreach ($allGroupsData as $groupKey => $group) {
                    foreach ($group['disciplines'] as $index => $groupDiscipline) {
                        if ($groupDiscipline['id'] === $completedDisciplineId) {
                            $allGroupsData[$groupKey]['disciplines'][$index]['completed_count_in_group']++;
                            break;
                        }
                    }
                }
            }

            $groupsData = $allGroupsData;

            // Generar nombres descriptivos para los grupos
            foreach ($groupsData as $groupKey => $group) {
                $disciplineNames = array_column($group['disciplines'], 'display_name');
                $groupsData[$groupKey]['group_name'] = count($disciplineNames) === 1
                    ? $disciplineNames[0]
                    : implode(' + ', $disciplineNames);
            }

            // Ordenar grupos por cantidad de clases completadas (descendente)
            uasort($groupsData, function ($a, $b) {
                if ($a['completed_count'] !== $b['completed_count']) {
                    return $b['completed_count'] <=> $a['completed_count'];
                }
                return $a['group_name'] <=> $b['group_name'];
            });

            // 4. Obtener shakes disponibles por consumir
            $availableShakes = $this->getAvailableShakesCount($user);

            // 5. Calcular clases completadas por disciplina individual
            $completedByDiscipline = [];

            foreach ($completedClasses as $classScheduleSeat) {
                $classSchedule = $classScheduleSeat->classSchedule;

                if (!$classSchedule || !$classSchedule->class || !$classSchedule->class->discipline) {
                    continue;
                }

                // Use discipline (singular)
                $discipline = $classSchedule->class->discipline;
                if (!$discipline) {
                    continue;
                }

                $disciplineId = $discipline->id;

                if (!isset($completedByDiscipline[$disciplineId])) {
                    $completedByDiscipline[$disciplineId] = [
                        'discipline_id' => $disciplineId,
                        'discipline_name' => $discipline->name,
                        'discipline_display_name' => $discipline->display_name,
                        'completed_count' => 0,
                        'classes' => [],
                    ];
                }

                $completedByDiscipline[$disciplineId]['completed_count']++;

                // Agregar detalles de la clase completada
                $completedByDiscipline[$disciplineId]['classes'][] = [
                    'id' => $classSchedule->id,
                    'class_name' => $classSchedule->class->name,
                    'scheduled_date' => $classSchedule->scheduled_date->format('Y-m-d'),
                    'start_time' => $classSchedule->start_time,
                    'end_time' => $classSchedule->end_time,
                    'completed_at' => $classSchedule->updated_at->format('Y-m-d H:i:s'),
                    'seat_code' => $classScheduleSeat->code ?? null,
                    'attendance_notes' => $classScheduleSeat->attendance_notes ?? null,
                ];
            }

            // Ordenar disciplinas por cantidad completada
            usort($completedByDiscipline, function ($a, $b) {
                return $b['completed_count'] <=> $a['completed_count'];
            });

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Historial obtenido exitosamente',
                'datoAdicional' => [
                    'summary' => [
                        'total_completed_classes' => $totalCompletedClasses,
                        'total_available_shakes' => $availableShakes,
                    ],
                    'completed_classes_by_group' => array_values($groupsData),
                    'completed_classes_by_discipline' => $completedByDiscipline,
                    'available_shakes_breakdown' => $this->getShakesBreakdown($user),
                ]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el historial',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener cantidad de shakes disponibles
     */
    private function getAvailableShakesCount($user): int
    {
        // Contar todos los pedidos de shake pendientes (gratuitos y regalos)
        $pendingShakeOrders = \App\Models\JuiceOrder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('total_amount_soles', 0) // Solo pedidos gratuitos/regalos
            ->withCount('details')
            ->get();

        $totalShakes = 0;
        foreach ($pendingShakeOrders as $order) {
            $totalShakes += $order->details_count; // Cantidad de bebidas en el pedido
        }

        return $totalShakes;
    }

    /**
     * Obtener todos los grupos de disciplinas del sistema
     */
    private function getAllDisciplineGroups(): array
    {
        // Obtener TODOS los paquetes activos del sistema con sus disciplinas
        $allPackages = \App\Models\Package::query()
            ->with(['disciplines'])
            ->where('buy_type', 'affordable')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('type', 'fixed')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'temporary')
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>=', now());
                    });
            })
            ->get();

        // Crear grupos únicos de disciplinas
        $disciplineGroups = [];
        $groupCounter = 0;

        foreach ($allPackages as $package) {
            if (!$package->disciplines || $package->disciplines->isEmpty()) {
                continue;
            }

            $disciplineIds = $package->disciplines->pluck('id')->sort()->values()->toArray();
            $groupKey = implode('-', $disciplineIds);

            if (!isset($disciplineGroups[$groupKey])) {
                $groupCounter++;
                $disciplineGroups[$groupKey] = [
                    'group_key' => $groupKey,
                    'disciplines' => [],
                    'group_name' => '',
                    'completed_count' => 0,
                    'classes' => [],
                ];

                foreach ($package->disciplines as $discipline) {
                    $disciplineGroups[$groupKey]['disciplines'][] = [
                        'id' => $discipline->id,
                        'name' => $discipline->name,
                        'display_name' => $discipline->display_name,
                        'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : null,
                        'color_hex' => $discipline->color_hex,
                        'order' => $discipline->order,
                    ];
                }

                usort($disciplineGroups[$groupKey]['disciplines'], function ($a, $b) {
                    return $a['order'] <=> $b['order'];
                });
            }
        }

        // Ordenar grupos por orden de disciplinas
        uasort($disciplineGroups, function ($a, $b) {
            return $a['group_name'] <=> $b['group_name'];
        });

        return $disciplineGroups;
    }

    /**
     * Obtener desglose detallado de shakes
     */
    private function getShakesBreakdown($user): array
    {
        $pendingShakeOrders = \App\Models\JuiceOrder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('total_amount_soles', 0)
            ->with(['details'])
            ->get();

        $breakdown = [];

        foreach ($pendingShakeOrders as $order) {
            $breakdown[] = [
                'order_id' => $order->id,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'total_shakes' => $order->details->count(),
                'notes' => $order->notes,
            ];
        }

        return $breakdown;
    }
}

