<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\MembreshipResource;
use App\Models\Log;
use App\Models\Membership;
use App\Models\UserMembership;
use App\Models\UserPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipController extends Controller
{




    /**
     * Obtener la categorias y sus niveles con información de clases válidas del usuario
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            $categorias = Membership::orderBy('level', 'asc')
                ->get();

            // Obtener las clases efectivas completadas del usuario para calcular progreso
            $user = Auth::user();
            $user->refresh();
            $totalCompletedClasses = $user->effective_completed_classes ?? 0;

            // Obtener todos los puntos del usuario
            $userPoints = \App\Models\UserPoint::where('user_id', $userId)
                ->with(['membership', 'activeMembership', 'package'])
                ->get();

            // Obtener las membresías activas y vigentes del usuario
            $userMemberships = UserMembership::where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->where(function ($query) {
                    $query->whereNull('activation_date')
                        ->orWhere('activation_date', '<=', now());
                })
                ->where('remaining_free_classes', '>', 0)
                ->with(['membership', 'discipline'])
                ->get();

            // Obtener los paquetes activos y vigentes del usuario
            $userPackages = UserPackage::where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->where(function ($query) {
                    $query->whereNull('activation_date')
                        ->orWhere('activation_date', '<=', now());
                })
                ->where('remaining_classes', '>', 0)
                ->with(['package.disciplines', 'package.membership'])
                ->get();

            // Mapear las categorías con información de clases válidas
            $membresiasConClases = $categorias->map(function ($membership) use ($userMemberships, $userPackages, $totalCompletedClasses, $categorias, $userPoints) {
                // Buscar membresías del usuario para esta membresía
                $userMembershipsForThis = $userMemberships->where('membership_id', $membership->id);
                
                // Buscar puntos ganados con esta membresía
                $pointsEarnedWithMembership = $userPoints->where('membresia_id', $membership->id);
                
                // Buscar puntos que están siendo usados con esta membresía activa
                $pointsUsedWithMembership = $userPoints->where('active_membership_id', $membership->id);
                
                // Calcular puntos totales ganados con esta membresía (solo no expirados)
                $totalPointsEarned = $pointsEarnedWithMembership->filter(function ($point) {
                    return !$point->isExpired();
                })->sum('quantity_point');
                
                // Calcular puntos totales que se están usando con esta membresía activa (solo no expirados)
                $totalPointsUsed = $pointsUsedWithMembership->filter(function ($point) {
                    return !$point->isExpired();
                })->sum('quantity_point');
                
                // Detalles de los puntos
                $pointsDetails = $pointsEarnedWithMembership->map(function ($point) {
                    return [
                        'id' => $point->id,
                        'quantity_point' => $point->quantity_point,
                        'date_expire' => $point->date_expire->format('Y-m-d'),
                        'is_expired' => $point->isExpired(),
                        'is_active' => $point->isActive(),
                        'active_membership_id' => $point->active_membership_id,
                        'active_membership_name' => $point->activeMembership->name ?? null,
                        'package_id' => $point->package_id,
                        'package_name' => $point->package->name ?? null,
                        'days_until_expire' => $point->isActive() ? now()->diffInDays($point->date_expire, false) : null,
                    ];
                })->values();

                // Calcular clases válidas de membresías
                $validMembershipClasses = $userMembershipsForThis->sum(function ($userMembership) {
                    // Verificar que esté vigente
                    if ($userMembership->expiry_date && $userMembership->expiry_date->isPast()) {
                        return 0;
                    }
                    if ($userMembership->activation_date && $userMembership->activation_date->isFuture()) {
                        return 0;
                    }
                    return $userMembership->remaining_free_classes;
                });

                // Buscar paquetes que otorgaron esta membresía
                // Los paquetes pueden tener una relación con membership a través del Package
                $packagesForThisMembership = $userPackages->filter(function ($userPackage) use ($membership) {
                    // Verificar si el paquete tiene esta membresía asociada
                    return $userPackage->package && $userPackage->package->membership_id === $membership->id;
                });

                // Calcular clases válidas de paquetes
                $validPackageClasses = $packagesForThisMembership->sum(function ($userPackage) {
                    // Verificar que esté vigente
                    if ($userPackage->expiry_date && $userPackage->expiry_date->isPast()) {
                        return 0;
                    }
                    if ($userPackage->activation_date && $userPackage->activation_date->isFuture()) {
                        return 0;
                    }
                    return $userPackage->remaining_classes;
                });

                // Total de clases válidas
                $totalValidClasses = $validMembershipClasses + $validPackageClasses;

                // Información detallada de las membresías del usuario
                $userMembershipsDetails = $userMembershipsForThis->map(function ($userMembership) {
                    $isValid = $userMembership->status === 'active' &&
                        (!$userMembership->expiry_date || $userMembership->expiry_date->isFuture()) &&
                        (!$userMembership->activation_date || $userMembership->activation_date->isPast()) &&
                        $userMembership->remaining_free_classes > 0;

                    return [
                        'id' => $userMembership->id,
                        'code' => $userMembership->code,
                        'discipline_id' => $userMembership->discipline_id,
                        'discipline_name' => $userMembership->discipline->name ?? null,
                        'total_free_classes' => $userMembership->total_free_classes,
                        'used_free_classes' => $userMembership->used_free_classes,
                        'remaining_free_classes' => $userMembership->remaining_free_classes,
                        'valid_classes' => $isValid ? $userMembership->remaining_free_classes : 0,
                        'activation_date' => $userMembership->activation_date?->format('Y-m-d'),
                        'expiry_date' => $userMembership->expiry_date?->format('Y-m-d'),
                        'days_remaining' => $userMembership->days_remaining,
                        'is_valid' => $isValid,
                        'is_expired' => $userMembership->is_expired,
                    ];
                })->values();

                // Información detallada de los paquetes relacionados
                $userPackagesDetails = $packagesForThisMembership->map(function ($userPackage) {
                    $isValid = $userPackage->status === 'active' &&
                        (!$userPackage->expiry_date || $userPackage->expiry_date->isFuture()) &&
                        (!$userPackage->activation_date || $userPackage->activation_date->isPast()) &&
                        $userPackage->remaining_classes > 0;

                    return [
                        'id' => $userPackage->id,
                        'package_code' => $userPackage->package_code,
                        'package_name' => $userPackage->package->name ?? null,
                        'total_classes' => $userPackage->package->classes_quantity ?? 0,
                        'used_classes' => $userPackage->used_classes,
                        'remaining_classes' => $userPackage->remaining_classes,
                        'valid_classes' => $isValid ? $userPackage->remaining_classes : 0,
                        'activation_date' => $userPackage->activation_date?->format('Y-m-d'),
                        'expiry_date' => $userPackage->expiry_date?->format('Y-m-d'),
                        'days_remaining' => $userPackage->days_remaining,
                        'is_valid' => $isValid,
                        'is_expired' => $userPackage->is_expired,
                    ];
                })->values();

                // Calcular progreso hacia esta membresía
                $isReached = $totalCompletedClasses >= $membership->class_completed;
                $classesNeeded = max(0, $membership->class_completed - $totalCompletedClasses);

                // Determinar la membresía anterior para calcular progreso
                $previousMembership = $categorias->where('level', '<', $membership->level)->sortByDesc('level')->first();
                $classesFromPrevious = $previousMembership
                    ? ($membership->class_completed - $previousMembership->class_completed)
                    : $membership->class_completed;

                $progressPercentage = 0;
                if ($classesFromPrevious > 0 && !$isReached) {
                    $classesCompletedInRange = $previousMembership
                        ? max(0, $totalCompletedClasses - $previousMembership->class_completed)
                        : $totalCompletedClasses;
                    $progressPercentage = round(($classesCompletedInRange / $classesFromPrevious) * 100, 2);
                } elseif ($isReached) {
                    $progressPercentage = 100;
                }

                return [
                    'id' => $membership->id,
                    'name' => $membership->name,
                    'level' => $membership->level,
                    'class_completed' => $membership->class_completed,
                    'slug' => $membership->slug,
                    'description' => $membership->description,
                    'is_active' => $membership->is_active,
                    'color_hex' => $membership->color_hex,
                    // Información de progreso hacia esta membresía
                    'progress' => [
                        'is_reached' => $isReached,
                        'classes_completed' => $totalCompletedClasses,
                        'classes_needed' => $classesNeeded,
                        'classes_from_previous' => $classesFromPrevious,
                        'progress_percentage' => max(0, min(100, $progressPercentage)),
                        'can_reach' => $classesNeeded <= 0,
                    ],
                    // Información de clases válidas del usuario
                    'user_valid_classes' => [
                        'total' => $totalValidClasses,
                        'from_memberships' => $validMembershipClasses,
                        'from_packages' => $validPackageClasses,
                        'has_valid_classes' => $totalValidClasses > 0,
                    ],
                    // Detalles de las membresías del usuario
                    'user_memberships' => $userMembershipsDetails,
                    // Detalles de los paquetes relacionados
                    'user_packages' => $userPackagesDetails,
                    // Información de puntos asociados a esta membresía
                    'points' => [
                        'total_earned' => $totalPointsEarned,
                        'total_used_with_active_membership' => $totalPointsUsed,
                        'points_details' => $pointsDetails,
                        'has_points' => $totalPointsEarned > 0,
                    ],
                ];
            });

            // Obtener la membresía activa del usuario (si tiene una)
            // Seleccionar la de mayor nivel (level) si hay múltiples activas
            // Solo considerar membresías con clases restantes disponibles
            $activeUserMembership = UserMembership::with(['membership'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->where(function ($query) {
                    $query->whereNull('activation_date')
                        ->orWhere('activation_date', '<=', now());
                })
                ->where('remaining_free_classes', '>', 0)
                ->join('memberships', 'user_memberships.membership_id', '=', 'memberships.id')
                ->orderBy('memberships.level', 'desc')
                ->select('user_memberships.*')
                ->first();

            // Determinar la membresía actual:
            // Siempre seleccionar la membresía de MAYOR NIVEL disponible, sin importar su origen
            // 1. Recolectar todas las membresías disponibles de todas las fuentes
            // 2. Seleccionar la de mayor nivel (level)
            // 3. Si no hay ninguna, usar la membresía basada en clases completadas
            
            $availableMemberships = collect();
            
            // 1. Membresías activas con clases restantes
            if ($activeUserMembership && $activeUserMembership->membership) {
                $availableMemberships->push($activeUserMembership->membership);
            }
            
            // 2. Membresías de paquetes activos
            $packageMemberships = $userPackages
                ->filter(function ($userPackage) {
                    return $userPackage->package && $userPackage->package->membership_id !== null;
                })
                ->map(function ($userPackage) {
                    return $userPackage->package->membership;
                })
                ->filter()
                ->unique('id');
            
            $availableMemberships = $availableMemberships->merge($packageMemberships);
            
            // 3. Membresías de puntos activos (tanto active_membership_id como membresia_id)
            $pointsMemberships = collect();
            
            // Membresías activas de los puntos
            $activePointsMemberships = $userPoints
                ->filter(function ($point) {
                    return !$point->isExpired() && $point->active_membership_id !== null;
                })
                ->map(function ($point) {
                    return $point->activeMembership;
                })
                ->filter()
                ->unique('id');
            
            // Membresías con las que se ganaron los puntos
            $earnedPointsMemberships = $userPoints
                ->filter(function ($point) {
                    return !$point->isExpired() && $point->membresia_id !== null;
                })
                ->map(function ($point) {
                    return $point->membership;
                })
                ->filter()
                ->unique('id');
            
            $pointsMemberships = $pointsMemberships->merge($activePointsMemberships)->merge($earnedPointsMemberships);
            $availableMemberships = $availableMemberships->merge($pointsMemberships);
            
            // Seleccionar la membresía de mayor nivel entre todas las disponibles
            $currentMembershipByProgress = $availableMemberships
                ->unique('id')
                ->sortByDesc('level')
                ->first();
            
            // Si no hay ninguna membresía disponible, calcular basado en clases completadas
            if (!$currentMembershipByProgress) {
                $currentMembershipByProgress = $categorias
                    ->filter(function ($m) use ($totalCompletedClasses) {
                        return $totalCompletedClasses >= $m->class_completed;
                    })
                    ->sortByDesc('level')
                    ->first();
            }

            // Determinar la siguiente membresía:
            // Si tiene membresía actual, la siguiente es la que tiene level mayor
            // Si no tiene membresía actual, la siguiente es la primera que requiere más clases
            $nextMembership = null;
            if ($currentMembershipByProgress) {
                // Buscar la membresía con level mayor que la actual
                $nextMembership = $categorias
                    ->filter(function ($m) use ($currentMembershipByProgress) {
                        return $m->level > $currentMembershipByProgress->level;
                    })
                    ->sortBy('level')
                    ->first();
            } else {
                // No tiene membresía actual, buscar la primera que requiere más clases
                $nextMembership = $categorias
                    ->filter(function ($m) use ($totalCompletedClasses) {
                        return $totalCompletedClasses < $m->class_completed;
                    })
                    ->sortBy('level')
                    ->first();
            }

            // Calcular resumen de puntos del usuario
            $activePoints = $userPoints->filter(function ($point) {
                return !$point->isExpired();
            });
            
            $expiredPoints = $userPoints->filter(function ($point) {
                return $point->isExpired();
            });
            
            $pointsSummary = [
                'total_points' => $activePoints->sum('quantity_point'),
                'total_points_by_membership_earned' => $activePoints->filter(function ($point) {
                    return $point->membresia_id !== null;
                })->groupBy('membresia_id')->map(function ($points) {
                    $firstPoint = $points->first();
                    return [
                        'membership_id' => $firstPoint->membresia_id,
                        'membership_name' => $firstPoint->membership->name ?? null,
                        'total_points' => $points->sum('quantity_point'),
                    ];
                })->values(),
                'total_points_by_active_membership' => $activePoints->filter(function ($point) {
                    return $point->active_membership_id !== null;
                })->groupBy('active_membership_id')->map(function ($points) {
                    $firstPoint = $points->first();
                    return [
                        'active_membership_id' => $firstPoint->active_membership_id,
                        'active_membership_name' => $firstPoint->activeMembership->name ?? null,
                        'total_points' => $points->sum('quantity_point'),
                    ];
                })->values(),
                'expired_points' => $expiredPoints->sum('quantity_point'),
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Lista de categorias ingresadas correctamente',
                'datoAdicional' => [
                    'membresias' => $membresiasConClases,
                    'user_progress' => [
                        'total_completed_classes' => $totalCompletedClasses,
                        'current_membership' => $currentMembershipByProgress ? [
                            'id' => $currentMembershipByProgress->id,
                            'name' => $currentMembershipByProgress->name,
                            'level' => $currentMembershipByProgress->level,
                            'class_completed_required' => $currentMembershipByProgress->class_completed,
                        ] : null,
                        'next_membership' => $nextMembership ? [
                            'id' => $nextMembership->id,
                            'name' => $nextMembership->name,
                            'level' => $nextMembership->level,
                            'class_completed_required' => $nextMembership->class_completed,
                            'classes_needed' => max(0, $nextMembership->class_completed - $totalCompletedClasses),
                        ] : null,
                    ],
                    'summary' => [
                        'total_memberships_available' => $categorias->count(),
                        'total_valid_classes' => $membresiasConClases->sum('user_valid_classes.total'),
                        'total_valid_membership_classes' => $membresiasConClases->sum('user_valid_classes.from_memberships'),
                        'total_valid_package_classes' => $membresiasConClases->sum('user_valid_classes.from_packages'),
                    ],
                    'points_summary' => $pointsSummary,
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener la membresía',
                'description' => 'Error al obtener las membresías',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las membresías',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }


    /**
     * Obtener la membresía activa del usuario (solo la de mayor nivel vigente)
     */
    public function getMyMemberships(Request $request)
    {
        try {
            $userId = Auth::id();

            // Obtener todas las membresías del usuario para debug
            $allUserMemberships = UserMembership::where('user_id', $userId)->get();

            // Obtener la membresía activa vigente con mayor nivel (level)
            // Primero obtener todas las membresías activas y vigentes
            $activeMemberships = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->get();

            // Si no hay membresías activas y vigentes, verificar si hay alguna membresía (aunque esté expirada)
            if ($activeMemberships->isEmpty()) {
                // Buscar cualquier membresía del usuario para debug
                $anyMembership = UserMembership::with(['membership', 'discipline'])
                    ->where('user_id', $userId)
                    ->first();

                if ($anyMembership) {
                    // Hay membresías pero no están activas o están expiradas
                    return response()->json([
                        'exito' => true,
                        'codMensaje' => 0,
                        'mensajeUsuario' => 'No tienes una membresía vigente',
                        'datoAdicional' => [
                            'has_membership' => false,
                            'membership' => null,
                            'debug' => [
                                'total_memberships' => $allUserMemberships->count(),
                                'active_memberships' => $activeMemberships->count(),
                                'found_membership' => [
                                    'id' => $anyMembership->id,
                                    'status' => $anyMembership->status,
                                    'expiry_date' => $anyMembership->expiry_date?->format('Y-m-d'),
                                    'is_expired' => $anyMembership->expiry_date && $anyMembership->expiry_date->isPast(),
                                    'membership_name' => $anyMembership->membership->name ?? null,
                                ]
                            ]
                        ]
                    ], 200);
                }

                return response()->json([
                    'exito' => true,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes una membresía vigente',
                    'datoAdicional' => [
                        'has_membership' => false,
                        'membership' => null,
                        'debug' => [
                            'total_memberships' => $allUserMemberships->count(),
                            'active_memberships' => $activeMemberships->count(),
                        ]
                    ]
                ], 200);
            }

            // Ordenar por nivel de membresía (mayor nivel primero), luego por fecha de expiración (la que expira antes primero)
            $membership = $activeMemberships->sort(function ($a, $b) {
                // Primero ordenar por level descendente (mayor level primero)
                $levelA = $a->membership->level ?? 0;
                $levelB = $b->membership->level ?? 0;
                
                if ($levelA !== $levelB) {
                    return $levelB <=> $levelA; // Orden descendente (mayor primero)
                }
                
                // Si tienen el mismo level, ordenar por fecha de expiración ascendente (la que expira antes primero)
                $expiryA = $a->expiry_date ? $a->expiry_date->timestamp : PHP_INT_MAX;
                $expiryB = $b->expiry_date ? $b->expiry_date->timestamp : PHP_INT_MAX;
                
                return $expiryA <=> $expiryB;
            })->first();

            // Cargar los datos de la membresía
            $membership->load('membership', 'discipline', 'sourcePackage');

            // Obtener información del usuario y su perfil
            $user = Auth::user();
            $user->refresh(); // Asegurar que tenemos los datos más actualizados
            
            // Obtener las clases efectivas completadas del usuario para calcular progreso
            $totalCompletedClasses = $user->effective_completed_classes ?? 0;

            // Obtener todas las membresías ordenadas por nivel para calcular progreso
            $allMemberships = Membership::where('is_active', true)
                ->orderBy('level', 'asc')
                ->get();

            // Determinar en qué membresía está actualmente el usuario basado en clases completadas
            $currentMembershipByProgress = null;
            $nextMembership = null;

            foreach ($allMemberships as $m) {
                // Si el usuario tiene suficientes clases completadas para esta membresía
                if ($totalCompletedClasses >= $m->class_completed) {
                    $currentMembershipByProgress = $m;
                } else {
                    // Si no alcanza esta membresía, esta es la siguiente
                    if (!$nextMembership) {
                        $nextMembership = $m;
                    }
                    break; // Ya encontramos la siguiente, no necesitamos seguir
                }
            }

            // Si el usuario alcanza todas las membresías, la siguiente es null
            if (!$nextMembership && $currentMembershipByProgress) {
                $nextMembership = null;
            }

            // Si no tiene ninguna membresía alcanzada, la primera es la siguiente
            if (!$currentMembershipByProgress && $allMemberships->isNotEmpty()) {
                $nextMembership = $allMemberships->first();
            }

            // Calcular progreso hacia la siguiente membresía
            $progressToNext = null;
            if ($nextMembership) {
                $classesNeeded = $nextMembership->class_completed - $totalCompletedClasses;
                $classesFromCurrent = $currentMembershipByProgress
                    ? ($nextMembership->class_completed - $currentMembershipByProgress->class_completed)
                    : $nextMembership->class_completed;

                $progressPercentage = $classesFromCurrent > 0
                    ? round((($classesFromCurrent - $classesNeeded) / $classesFromCurrent) * 100, 2)
                    : 0;

                $progressToNext = [
                    'next_membership' => [
                        'id' => $nextMembership->id,
                        'name' => $nextMembership->name,
                        'level' => $nextMembership->level,
                        'class_completed_required' => $nextMembership->class_completed,
                        'slug' => $nextMembership->slug,
                        'description' => $nextMembership->description,
                        'icon' => $nextMembership->icon,
                        'color_hex' => $nextMembership->color_hex,
                    ],
                    'classes_completed' => $totalCompletedClasses,
                    'classes_needed' => max(0, $classesNeeded),
                    'classes_from_current' => $classesFromCurrent,
                    'progress_percentage' => max(0, min(100, $progressPercentage)),
                    'can_reach_next' => $classesNeeded <= 0,
                ];
            }

            // Obtener el perfil del usuario (ya tenemos $user de arriba)
            $userProfile = $user->profile;

            $formattedMembership = [
                'id' => $membership->id,
                'code' => $membership->code, // Código de membresía (formato tarjeta: XXXX-XXXX-XXXX-XXXX)
                'code_digits' => $membership->code_digits, // Código sin guiones
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'code' => $user->code,
                    'birth_date' => $userProfile?->birth_date ? $userProfile->birth_date->format('Y-m-d') : null,
                    'birth_date_formatted' => $userProfile?->birth_date ? $userProfile->birth_date->format('d/m/Y') : null,
                ],
                'membership' => [
                    'id' => $membership->membership->id ?? null,
                    'name' => $membership->membership->name ?? 'N/A',
                    'level' => $membership->membership->level ?? null,
                    'slug' => $membership->membership->slug ?? null,
                    'description' => $membership->membership->description ?? null,
                    'icon' => $membership->membership->icon ?? null,
                    'color_hex' => $membership->membership->color_hex ?? null,
                    'colors' => $membership->membership->colors ?? null, // Array de colores
                    'is_active' => $membership->membership->is_active ?? null,
                ],
                'discipline' => [
                    'id' => $membership->discipline->id ?? null,
                    'name' => $membership->discipline->name ?? 'N/A',
                ],
                'free_classes' => [
                    'total' => $membership->total_free_classes,
                    'used' => $membership->used_free_classes,
                    'remaining' => $membership->remaining_free_classes,
                    'percentage_used' => $membership->total_free_classes > 0
                        ? round(($membership->used_free_classes / $membership->total_free_classes) * 100, 2)
                        : 0,
                ],
                'dates' => [
                    'activation_date' => $membership->activation_date ? $membership->activation_date->format('Y-m-d') : null,
                    'expiry_date' => $membership->expiry_date ? $membership->expiry_date->format('Y-m-d') : null,
                    'days_remaining' => $membership->days_remaining,
                ],
                'status' => [
                    'code' => $membership->status,
                    'display' => $membership->status_display_name,
                    'is_valid' => $membership->is_valid,
                    'is_expired' => $membership->is_expired,
                    'has_free_classes' => $membership->has_free_classes,
                ],
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Membresía obtenida exitosamente',
                'datoAdicional' => [
                    'has_membership' => true,
                    'membership' => $formattedMembership,
                    'progress' => [
                        'total_completed_classes' => $totalCompletedClasses,
                        'current_membership_by_progress' => $currentMembershipByProgress ? [
                            'id' => $currentMembershipByProgress->id,
                            'name' => $currentMembershipByProgress->name,
                            'level' => $currentMembershipByProgress->level,
                            'class_completed_required' => $currentMembershipByProgress->class_completed,
                            'slug' => $currentMembershipByProgress->slug,
                            'description' => $currentMembershipByProgress->description,
                            'icon' => $currentMembershipByProgress->icon,
                            'color_hex' => $currentMembershipByProgress->color_hex,
                        ] : null,
                        'progress_to_next' => $progressToNext,
                    ],
                ]
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener la membresía activa del usuario (solo la de mayor nivel vigente)',
                'description' => 'Error al obtener las membresías',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener las membresías',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Verificar si la membresía está vigente para una disciplina específica
     */
    public function checkMembershipStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'discipline_id' => 'required|integer|exists:disciplines,id'
            ]);

            $userId = Auth::id();
            $disciplineId = $request->integer('discipline_id');

            // Verificar si tiene membresía vigente para esta disciplina
            $membership = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->where('discipline_id', $disciplineId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->where('remaining_free_classes', '>', 0)
                ->orderBy('remaining_free_classes', 'desc')
                ->orderBy('expiry_date', 'asc')
                ->first();

            if (!$membership) {
                return response()->json([
                    'exito' => true,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'No tienes membresía vigente para esta disciplina',
                    'datoAdicional' => [
                        'has_membership' => false,
                        'discipline_id' => $disciplineId,
                    ]
                ], 200);
            }

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Tienes membresía vigente',
                'datoAdicional' => [
                    'has_membership' => true,
                    'discipline' => [
                        'id' => $membership->discipline->id ?? null,
                        'name' => $membership->discipline->name ?? 'N/A',
                    ],
                    'membership' => [
                        'id' => $membership->membership->id ?? null,
                        'name' => $membership->membership->name ?? 'N/A',
                        'level' => $membership->membership->level ?? null,
                    ],
                    'free_classes_remaining' => $membership->remaining_free_classes,
                    'free_classes_total' => $membership->total_free_classes,
                    'free_classes_used' => $membership->used_free_classes,
                    'expiry_date' => $membership->expiry_date->format('Y-m-d'),
                    'days_remaining' => $membership->days_remaining,
                    'is_valid' => $membership->is_valid,
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Verificar si la membresía está vigente para una disciplina específica',
                'description' => 'Datos de entrada inválidos',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Datos de entrada inválidos',
                'datoAdicional' => $e->errors()
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Verificar si la membresía está vigente para una disciplina específica',
                'description' => 'Error al verificar el estado de la membresía',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al verificar el estado de la membresía',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener un resumen completo de membresías y paquetes del usuario
     */
    public function getMembershipSummary(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Obtener todas las membresías (activas e inactivas)
            $allMemberships = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->get();

            $activeMemberships = $allMemberships->where('status', 'active')
                ->where(function ($m) {
                    return $m->expiry_date === null || $m->expiry_date->isFuture();
                })
                ->values();

            // Obtener paquetes activos
            $activePackages = UserPackage::with(['package.disciplines'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('expiry_date', '>', now())
                ->where('remaining_classes', '>', 0)
                ->get();

            // Resumen de membresías
            $membershipsSummary = [
                'total' => $allMemberships->count(),
                'active' => $activeMemberships->count(),
                'expired' => $allMemberships->where('status', 'expired')->count(),
                'by_discipline' => $activeMemberships->groupBy('discipline_id')->map(function ($ms) {
                    return [
                        'discipline_name' => $ms->first()->discipline->name ?? 'N/A',
                        'count' => $ms->count(),
                        'total_free_classes' => $ms->sum('remaining_free_classes'),
                    ];
                })->values(),
            ];

            // Resumen de paquetes
            $packagesSummary = [
                'total' => $activePackages->count(),
                'total_classes_remaining' => $activePackages->sum('remaining_classes'),
                'total_classes_used' => $activePackages->sum('used_classes'),
                'by_package' => $activePackages->map(function ($userPackage) {
                    return [
                        'package_name' => $userPackage->package->name ?? 'N/A',
                        'remaining_classes' => $userPackage->remaining_classes,
                        'used_classes' => $userPackage->used_classes,
                    ];
                }),
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Resumen de membresías obtenido exitosamente',
                'datoAdicional' => [
                    'memberships' => $membershipsSummary,
                    'packages' => $packagesSummary,
                    'total_free_classes_available' => $activeMemberships->sum('remaining_free_classes'),
                    'total_paid_classes_available' => $activePackages->sum('remaining_classes'),
                ]
            ], 200);
        } catch (\Throwable $e) {

            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Verificar si la membresía está vigente para una disciplina específica',
                'description' => 'Error al obtener el resumen de membresías',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el resumen de membresías',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Obtener la membresía actual del usuario y el progreso hacia la siguiente
     */
    public function getMyMembershipProgress(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'exito' => false,
                    'codMensaje' => 0,
                    'mensajeUsuario' => 'Usuario no autenticado',
                    'datoAdicional' => []
                ], 200);
            }

            // Contar las clases completadas del usuario
            $totalCompletedClasses = \App\Models\ClassScheduleSeat::where('user_id', $userId)
                ->where('status', 'Completed')
                ->count();

            // Obtener todas las membresías ordenadas por nivel (de menor a mayor)
            $allMemberships = Membership::where('is_active', true)
                ->orderBy('level', 'asc')
                ->get();

            // Determinar en qué membresía está actualmente el usuario
            // La membresía actual es la más alta que puede alcanzar según sus clases completadas
            $currentMembership = null;
            $nextMembership = null;

            foreach ($allMemberships as $membership) {
                // Si el usuario tiene suficientes clases completadas para esta membresía
                if ($totalCompletedClasses >= $membership->class_completed) {
                    $currentMembership = $membership;
                } else {
                    // Si no alcanza esta membresía, esta es la siguiente
                    if (!$nextMembership) {
                        $nextMembership = $membership;
                    }
                    break; // Ya encontramos la siguiente, no necesitamos seguir
                }
            }

            // Si el usuario alcanza todas las membresías, la siguiente es null
            if (!$nextMembership && $currentMembership) {
                // El usuario está en la membresía más alta
                $nextMembership = null;
            }

            // Si no tiene ninguna membresía alcanzada, la primera es la siguiente
            if (!$currentMembership && $allMemberships->isNotEmpty()) {
                $nextMembership = $allMemberships->first();
            }

            // Calcular progreso hacia la siguiente membresía
            $progress = null;
            if ($nextMembership) {
                $classesNeeded = $nextMembership->class_completed - $totalCompletedClasses;
                $classesFromCurrent = $currentMembership
                    ? ($nextMembership->class_completed - $currentMembership->class_completed)
                    : $nextMembership->class_completed;

                $progressPercentage = $classesFromCurrent > 0
                    ? round((($classesFromCurrent - $classesNeeded) / $classesFromCurrent) * 100, 2)
                    : 0;

                $progress = [
                    'next_membership' => [
                        'id' => $nextMembership->id,
                        'name' => $nextMembership->name,
                        'level' => $nextMembership->level,
                        'class_completed_required' => $nextMembership->class_completed,
                    ],
                    'classes_completed' => $totalCompletedClasses,
                    'classes_needed' => max(0, $classesNeeded),
                    'classes_from_current' => $classesFromCurrent,
                    'progress_percentage' => max(0, min(100, $progressPercentage)),
                    'can_reach_next' => $classesNeeded <= 0,
                ];
            }

            // Obtener la membresía activa del usuario (si tiene una UserMembership activa)
            $activeUserMembership = UserMembership::with(['membership', 'discipline'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                })
                ->join('memberships', 'user_memberships.membership_id', '=', 'memberships.id')
                ->orderBy('memberships.level', 'desc')
                ->select('user_memberships.*')
                ->first();

            // Formatear respuesta
            $response = [
                'total_completed_classes' => $totalCompletedClasses,
                'current_membership_by_progress' => $currentMembership ? [
                    'id' => $currentMembership->id,
                    'name' => $currentMembership->name,
                    'level' => $currentMembership->level,
                    'class_completed_required' => $currentMembership->class_completed,
                    'slug' => $currentMembership->slug,
                    'description' => $currentMembership->description,
                    'icon' => $currentMembership->icon,
                    'color_hex' => $currentMembership->color_hex,
                ] : null,
                'active_user_membership' => $activeUserMembership ? [
                    'id' => $activeUserMembership->id,
                    'code' => $activeUserMembership->code,
                    'membership' => [
                        'id' => $activeUserMembership->membership->id ?? null,
                        'name' => $activeUserMembership->membership->name ?? null,
                        'level' => $activeUserMembership->membership->level ?? null,
                    ],
                    'discipline' => [
                        'id' => $activeUserMembership->discipline->id ?? null,
                        'name' => $activeUserMembership->discipline->name ?? null,
                    ],
                    'remaining_free_classes' => $activeUserMembership->remaining_free_classes,
                    'expiry_date' => $activeUserMembership->expiry_date?->format('Y-m-d'),
                    'days_remaining' => $activeUserMembership->days_remaining,
                ] : null,
                'progress_to_next' => $progress,
                'all_memberships' => $allMemberships->map(function ($membership) use ($totalCompletedClasses) {
                    $isReached = $totalCompletedClasses >= $membership->class_completed;
                    $classesNeeded = max(0, $membership->class_completed - $totalCompletedClasses);

                    return [
                        'id' => $membership->id,
                        'name' => $membership->name,
                        'level' => $membership->level,
                        'class_completed_required' => $membership->class_completed,
                        'is_reached' => $isReached,
                        'classes_needed' => $isReached ? 0 : $classesNeeded,
                        'slug' => $membership->slug,
                        'description' => $membership->description,
                        'icon' => $membership->icon,
                        'color_hex' => $membership->color_hex,
                    ];
                })->values(),
            ];

            return response()->json([
                'exito' => true,
                'codMensaje' => 1,
                'mensajeUsuario' => 'Progreso de membresía obtenido exitosamente',
                'datoAdicional' => $response
            ], 200);
        } catch (\Throwable $e) {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'Obtener la membresía actual del usuario y el progreso hacia la siguiente',
                'description' => 'Error al obtener el progreso de membresía',
                'data' => $e->getMessage(),
            ]);

            return response()->json([
                'exito' => false,
                'codMensaje' => 0,
                'mensajeUsuario' => 'Error al obtener el progreso de membresía',
                'datoAdicional' => $e->getMessage()
            ], 200);
        }
    }
}
