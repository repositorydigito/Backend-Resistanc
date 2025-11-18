<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\ClassScheduleResource;
use App\Http\Resources\DisciplineResource;
use App\Http\Resources\InstructorResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\ProductResource;
use App\Models\ClassSchedule;
use App\Models\Discipline;
use App\Models\Instructor;
use App\Models\Log;
use App\Models\Post;
use App\Models\Product;
use DragonCode\Contracts\Cashier\Auth\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;

/**
 * @tags Inicio
 */
final class HomeController extends Controller
{
    /**
     * Datos de la pantalla de inicio
     */
    public function index()
    {

        try {
            $user = request()->user(); // más seguro que Auth::user()

            // Cantidad de reservas de asientos completadas
            $classSchedulesCompletedCount = $user->completedSeatReservations()->count();

            $classSchedulesPendingCount = $user->pendingSeatReservations()->count();

            // Clases disponibles del usuario
            $availableClassesCount = $user->getAvailableClassesCount();
            $availableClassesByDiscipline = $user->getAvailableClassesByDiscipline();

            // Paquetes activos del usuario
            $activePackagesCount = $user->getActivePackagesCount();

            // Obtener grupos de disciplinas con clases disponibles
            $disciplineGroupsWithClasses = $this->getDisciplineGroupsWithAvailableClasses($user);

            // Obtener cantidad de shakes disponibles
            $availableShakesCount = $this->getAvailableShakesCount($user);

            // Instructores activos con disciplina
            $instructors = Instructor::with('disciplines')
                ->whereHas('disciplines', fn($q) => $q->where('status', 'active'))
                ->orderBy('name')
                ->limit(10)
                ->get();

            $disciplines = Discipline::orderBy('order', 'asc')
                ->where('is_active', true)
                ->get();


            $classSchedulesMe = $user->upcomingSeatReservations()
                ->where(function ($query) {
                    $query->where('status', 'scheduled')
                        ->orWhere('status', 'in_progress');
                })
                ->whereHas('classSchedule.class', fn($q) => $q->where('status', 'active'))
                ->whereHas('classSchedule.studio', fn($q) => $q->where('status', 'active'))
                ->with(['classSchedule.class', 'classSchedule.studio', 'seat'])
                ->orderBy('reserved_at')
                ->limit(10)
                ->get();

            // 🎯 Calcular la fecha máxima basada en la membresía del usuario
            $maxScheduledDate = null;

            // Obtener todas las membresías activas del usuario
            $userMemberships = $user->userMemberships()
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
                    // Si el usuario tiene membresía con classes_before, puede ver clases hasta X días en el futuro
                    // Ejemplo: si classes_before = 7, puede ver clases hasta 7 días en el futuro
                    $maxScheduledDate = now()->addDays($maxClassesBefore)->toDateString();
                }
            }

            $classSchedulesQuery = ClassSchedule::with(['class', 'studio'])
                ->where('scheduled_date', '>=', now()->toDateString())
                ->where('status', 'scheduled');

            // Aplicar límite de membresía si existe
            if ($maxScheduledDate) {
                $classSchedulesQuery->where('scheduled_date', '<=', $maxScheduledDate);
            }

            $classSchedules = $classSchedulesQuery
                ->orderBy('scheduled_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->limit(10)
                ->get();

            $products = Product::with('category', 'productBrand')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $posts = Post::with('category', 'tags')
                ->where('status', 'published')
                ->orderBy('is_featured', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'data' => [
                    'user' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ],
                        'info' => [
                            'completedClassSchedulesCount' => $classSchedulesCompletedCount,
                            'pendingClassSchedulesCount' => $classSchedulesPendingCount,
                            'availableClassesCount' => $availableClassesCount,
                            'availableClassesByDiscipline' => $availableClassesByDiscipline,
                            'activePackagesCount' => $activePackagesCount,
                            'disciplineGroupsWithClasses' => $disciplineGroupsWithClasses,
                            'availableShakesCount' => $availableShakesCount,
                        ],

                        'paymentMethods' => $user->storedPaymentMethods ? $user->storedPaymentMethods->map(function ($method) {
                            return [
                                'id' => $method->id,
                                'type' => $method->type,
                                'details' => $method->details,
                            ];
                        }) : [],
                        'packages' => $user->packages ? $user->packages->map(function ($package) {
                            return [
                                'id' => $package->id,
                                'name' => $package->name,
                                'description' => $package->description,
                                'price' => $package->price,
                                'status' => $package->status,
                            ];
                        }) : [],

                    ],
                    'disciplines' => DisciplineResource::collection($disciplines),
                    'instructors' => InstructorResource::collection($instructors),
                    'classSchedules' => ClassScheduleResource::collection($classSchedules),
                    'classSchedulesMe' => ClassScheduleResource::collection($classSchedulesMe),
                    'products' => ProductResource::collection($products),
                    'posts' => PostResource::collection($posts),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => FacadesAuth::id(),
                'action' => 'Obtener historial de clases completadas del usuario',
                'description' => 'Error al obtener el historial',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al obtener los datos de inicio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener grupos de disciplinas con clases disponibles del usuario
     */
    private function getDisciplineGroupsWithAvailableClasses($user): array
    {
        // 1. Obtener TODOS los paquetes activos del sistema con sus disciplinas
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

        // 2. Crear grupos únicos de disciplinas (inicialmente con 0 clases)
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
                    'id' => $groupCounter,
                    'group_key' => $groupKey,
                    'disciplines' => [],
                    'disciplines_count' => count($disciplineIds),
                    'available_classes' => 0,
                    'group_name' => '',
                ];

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

                usort($disciplineGroups[$groupKey]['disciplines'], function ($a, $b) {
                    return $a['order'] <=> $b['order'];
                });
            }
        }

        // 3. Obtener paquetes del usuario
        $userPackages = $user->userPackages()
            ->with(['package.disciplines'])
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_classes', '>', 0)
            ->get();

        // 4. Sumar las clases disponibles del usuario a cada grupo
        foreach ($userPackages as $userPackage) {
            if (!$userPackage->package || !$userPackage->package->disciplines) {
                continue;
            }

            $disciplineIds = $userPackage->package->disciplines->pluck('id')->sort()->values()->toArray();
            $groupKey = implode('-', $disciplineIds);

            if (isset($disciplineGroups[$groupKey])) {
                $disciplineGroups[$groupKey]['available_classes'] += $userPackage->remaining_classes;
            }
        }

        // 5. Obtener membresías del usuario
        $userMemberships = $user->userMemberships()
            ->with(['discipline'])
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_free_classes', '>', 0)
            ->get();

        // 6. Sumar las clases de membresías a los grupos correspondientes
        foreach ($userMemberships as $userMembership) {
            if (!$userMembership->discipline) {
                continue;
            }

            $disciplineId = $userMembership->discipline_id;

            foreach ($disciplineGroups as $groupKey => &$group) {
                $existingDisciplineIds = array_column($group['disciplines'], 'id');
                if (in_array($disciplineId, $existingDisciplineIds)) {
                    $group['available_classes'] += $userMembership->remaining_free_classes;
                    break;
                }
            }
        }

        // 7. Generar nombres descriptivos
        foreach ($disciplineGroups as &$group) {
            $disciplineNames = array_column($group['disciplines'], 'display_name');
            $group['group_name'] = count($disciplineNames) === 1
                ? $disciplineNames[0]
                : implode(' + ', $disciplineNames);
        }

        // 8. Ordenar: primero los que tienen clases (descendente), luego los que no tienen
        uasort($disciplineGroups, function ($a, $b) {
            if (($a['available_classes'] > 0) !== ($b['available_classes'] > 0)) {
                return ($b['available_classes'] > 0) <=> ($a['available_classes'] > 0);
            }
            if ($a['available_classes'] !== $b['available_classes']) {
                return $b['available_classes'] <=> $a['available_classes'];
            }
            return $a['group_name'] <=> $b['group_name'];
        });

        return array_values($disciplineGroups);
    }

    /**
     * Obtener cantidad de shakes disponibles del usuario desde pedidos pendientes (incluyendo regalos por membresía)
     */
    private function getAvailableShakesCount($user): int
    {
        // Contar todos los pedidos de shake no entregados (pagados o gratuitos)
        $pendingShakeOrders = \App\Models\JuiceOrder::where('user_id', $user->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->with('details')
            ->get();

        return $pendingShakeOrders->sum(function ($order) {
            return $order->details->sum('quantity');
        });
    }
}
