<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplineResource;
use App\Models\Discipline;
use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Disciplinas
 */
final class DisciplineController extends Controller
{
    /**
     * Lista todas las disciplinas activas del sistema
     *
     */
    public function index(Request $request)
    {

        try {
            $query = Discipline::query()
                ->active()
                ->where('is_active', true)
                // ->whereHas('packages')
                ->orderBy('order', 'asc')
                ->orderBy('display_name', 'asc');

            // Incluir contadores si se solicita
            if ($request->boolean('include_counts', false)) {
                $query->withCount(['classes', 'instructors']);
            }

            // Si no se especifica per_page, devolver todo sin paginar
            if ($request->has('per_page')) {
                $disciplines = $query->paginate(
                    perPage: $request->integer('per_page', 15),
                    page: $request->integer('page', 1)
                );
            } else {
                $disciplines = $query->get();
            }
            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de disciplinas obtenida correctamente',
                'datoAdicional' => DisciplineResource::collection($disciplines)
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener lista de ',
                'description' => 'Error al obtener las disciplinas',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las disciplinas',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }



    /**
     * Obtener grupos únicos de disciplinas que existen en los paquetes
     */
    public function indexGroup(Request $request)
    {
        try {
            // Validar parámetros opcionales
            $request->validate([
                'is_membresia' => 'sometimes|boolean',

            ]);

            // Obtener paquetes activos con sus disciplinas
            $packagesQuery = \App\Models\Package::query()
                ->with(['disciplines'])
                ->where('buy_type', 'affordable')
                ->active()
                ->where(function ($query) {
                    // Paquetes fijos o temporales vigentes
                    $query->where('type', 'fixed')
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('type', 'temporary')
                                ->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                        });
                });

            // Aplicar filtros opcionales
            if ($request->has('is_membresia')) {
                $packagesQuery->where('is_membresia', $request->boolean('is_membresia'));
            }

            //  if ($request->filled('mode_type')) {
            //      $packagesQuery->where('mode_type', $request->string('mode_type'));
            //  }

            // if ($request->filled('commercial_type')) {
            //     $packagesQuery->where('commercial_type', $request->string('commercial_type'));
            // }

            $packages = $packagesQuery->get();

            // Crear grupos únicos de disciplinas
            $disciplineGroups = [];
            $groupCounter = 0;

            foreach ($packages as $package) {
                // Crear una clave única para el grupo de disciplinas de este paquete
                $disciplineIds = $package->disciplines->pluck('id')->sort()->values()->toArray();
                $groupKey = implode('-', $disciplineIds);

                // Si el grupo no existe, crearlo
                if (!isset($disciplineGroups[$groupKey])) {
                    $groupCounter++;
                    $disciplineGroups[$groupKey] = [
                        'id' => $groupCounter,
                        'group_key' => $groupKey,
                        'disciplines' => [],
                        'disciplines_count' => count($disciplineIds),
                        'packages_count' => 0,
                        'group_name' => '', // Se generará después
                    ];

                    // Agregar información de cada disciplina en el grupo
                    foreach ($package->disciplines as $discipline) {
                        $disciplineGroups[$groupKey]['disciplines'][] = [
                            'id' => $discipline->id,
                            'name' => $discipline->name,
                            'display_name' => $discipline->display_name,
                            'icon_url' => $discipline->icon_url ? asset('storage/' . $discipline->icon_url) : asset('default/icon.png'),
                            'color_hex' => $discipline->color_hex,
                            'order' => $discipline->order,
                        ];
                    }

                    // Ordenar disciplinas por orden
                    usort($disciplineGroups[$groupKey]['disciplines'], function ($a, $b) {
                        return $a['order'] <=> $b['order'];
                    });
                }

                // Incrementar contador de paquetes para este grupo
                $disciplineGroups[$groupKey]['packages_count']++;
            }

            // Generar nombres descriptivos para cada grupo
            foreach ($disciplineGroups as &$group) {
                $disciplineNames = array_column($group['disciplines'], 'display_name');

                if (count($disciplineNames) === 1) {
                    $group['group_name'] = $disciplineNames[0];
                } else {
                    $group['group_name'] = implode(' + ', $disciplineNames);
                }
            }

            // Ordenar grupos por cantidad de disciplinas y luego por nombre
            uasort($disciplineGroups, function ($a, $b) {
                if ($a['disciplines_count'] === $b['disciplines_count']) {
                    return $a['group_name'] <=> $b['group_name'];
                }
                return $a['disciplines_count'] <=> $b['disciplines_count'];
            });

            // Convertir a array indexado
            $disciplineGroups = array_values($disciplineGroups);

            // Calcular estadísticas
            $stats = [
                'total_groups' => count($disciplineGroups),
                'total_disciplines' => collect($disciplineGroups)->sum('disciplines_count'),
                'filters_applied' => [
                    'is_membresia' => $request->has('is_membresia') ? $request->boolean('is_membresia') : null,
                    // 'mode_type' => $request->string('mode_type'),
                    // 'commercial_type' => $request->string('commercial_type'),
                ]
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Grupos de disciplinas obtenidos correctamente',
                'datoAdicional' =>  $disciplineGroups,
                // 'stats' => $stats

            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener grupos de disciplinas',
                'datoAdicional' => $th->getMessage()
            ], 200);
        }
    }
}
