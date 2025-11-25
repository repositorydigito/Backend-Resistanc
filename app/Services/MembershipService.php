<?php

namespace App\Services;

use App\Models\UserMembership;
use App\Models\UserPackage;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    /**
     * Verificar si un usuario puede usar una clase gratis para una disciplina específica.
     */
    public function canUseFreeClass(int $userId, int $disciplineId): bool
    {
        return UserMembership::hasAvailableFreeClasses($userId, $disciplineId);
    }

    /**
     * Obtener las opciones de pago disponibles para un usuario (paquetes + clases gratis).
     */
    public function getAvailablePaymentOptions(int $userId, int $disciplineId): array
    {
        $options = [];

        // 1. Verificar si tiene clases gratis disponibles
        if ($this->canUseFreeClass($userId, $disciplineId)) {
            $bestMembership = UserMembership::getBestAvailableMembership($userId, $disciplineId);

            if ($bestMembership) {
                $options[] = [
                    'type' => 'free_class',
                    'id' => $bestMembership->id,
                    'name' => "Clase gratis - {$bestMembership->membership->name}",
                    'description' => "Clase gratis de {$bestMembership->discipline->name}",
                    'remaining_classes' => $bestMembership->remaining_free_classes,
                    'expiry_date' => $bestMembership->expiry_date->format('Y-m-d'),
                    'cost' => 0,
                ];
            }
        }

        // 2. Verificar paquetes disponibles para la disciplina
        $availablePackages = UserPackage::with(['package.disciplines'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->where('remaining_classes', '>', 0)
            ->whereHas('package', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('package.disciplines', function ($query) use ($disciplineId) {
                $query->where('disciplines.id', $disciplineId);
            })
            ->get();

        foreach ($availablePackages as $userPackage) {
            $options[] = [
                'type' => 'package',
                'id' => $userPackage->id,
                'name' => $userPackage->package->name,
                'description' => "Paquete de {$userPackage->package->classes_quantity} clases",
                'remaining_classes' => $userPackage->remaining_classes,
                'expiry_date' => $userPackage->expiry_date->format('Y-m-d'),
                'cost' => 0, // Ya pagado
            ];
        }

        return $options;
    }

    /**
     * Usar una clase gratis para una reserva.
     */
    public function useFreeClass(int $userId, int $disciplineId, int $classScheduleId): array
    {
        DB::beginTransaction();

        try {
            // Obtener la mejor membresía disponible
            $membership = UserMembership::getBestAvailableMembership($userId, $disciplineId);

            if (!$membership) {
                throw new \Exception('No hay clases gratis disponibles para esta disciplina');
            }

            // Usar la clase gratis
            if (!$membership->useFreeClasses(1)) {
                throw new \Exception('No se pudo usar la clase gratis');
            }

            // Aquí podrías crear un registro de booking/reserva
            // $booking = Booking::create([...]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Clase gratis utilizada exitosamente',
                'membership_id' => $membership->id,
                'remaining_free_classes' => $membership->remaining_free_classes,
                'discipline_name' => $membership->discipline->name,
            ];

        } catch (\Exception $e) {
            DB::rollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener estadísticas de membresías del usuario.
     */
    public function getUserMembershipStats(int $userId): array
    {
        $totalMemberships = UserMembership::where('user_id', $userId)->count();
        $activeMemberships = UserMembership::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->count();

        $totalFreeClasses = UserMembership::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->sum('remaining_free_classes');

        $usedFreeClasses = UserMembership::where('user_id', $userId)
            ->where('status', 'active')
            ->sum('used_free_classes');

        return [
            'total_memberships' => $totalMemberships,
            'active_memberships' => $activeMemberships,
            'total_free_classes_available' => $totalFreeClasses,
            'total_free_classes_used' => $usedFreeClasses,
        ];
    }

    /**
     * Evalúa si un usuario ha alcanzado el requisito para una nueva membresía
     * y la crea automáticamente si corresponde.
     *
     * @param int $userId ID del usuario
     * @param int|null $disciplineId ID de la disciplina (opcional, se obtiene de la clase completada si no se proporciona)
     * @return array Información sobre si se creó una membresía
     */
    public function evaluateAndCreateMembershipForUser(int $userId, ?int $disciplineId = null): array
    {
        try {
            // Log inicial para debugging
            \App\Models\Log::create([
                'user_id' => $userId,
                'action' => 'Evaluación de membresía iniciada',
                'description' => "Iniciando evaluación de membresía para usuario {$userId}" . ($disciplineId ? " con disciplina {$disciplineId}" : ""),
                'data' => [
                    'user_id' => $userId,
                    'discipline_id' => $disciplineId,
                ],
            ]);

            // Obtener el usuario para acceder a sus clases efectivas completadas
            $user = \App\Models\User::find($userId);
            
            if (!$user) {
                throw new \RuntimeException("Usuario con ID {$userId} no encontrado");
            }

            // SIEMPRE recalcular clases efectivas antes de evaluar membresías
            // Esto asegura que tengamos el valor más actualizado después de completar una clase
            $totalCompletedClasses = $user->calculateAndUpdateEffectiveCompletedClasses();
            $user->refresh();
            
            // Log para debug con información detallada
            \App\Models\Log::create([
                'user_id' => $userId,
                'action' => 'Evaluación de membresía - Clases efectivas calculadas',
                'description' => "Usuario {$userId}: Clases efectivas completadas = {$totalCompletedClasses}. Se evaluará si puede obtener una nueva membresía.",
                'data' => [
                    'effective_completed_classes' => $totalCompletedClasses,
                    'user_id' => $userId,
                    'discipline_id' => $disciplineId,
                ],
            ]);

            // Obtener todas las membresías ordenadas por nivel
            $allMemberships = \App\Models\Membership::where('is_active', true)
                ->orderBy('level', 'asc')
                ->get();

            // Log: membresías encontradas
            \App\Models\Log::create([
                'user_id' => $userId,
                'action' => 'Membresías consultadas',
                'description' => "Se encontraron " . $allMemberships->count() . " membresías activas. Clases completadas: {$totalCompletedClasses}",
                'data' => [
                    'total_memberships' => $allMemberships->count(),
                    'total_completed_classes' => $totalCompletedClasses,
                    'memberships' => $allMemberships->map(function ($m) {
                        return [
                            'id' => $m->id,
                            'name' => $m->name,
                            'level' => $m->level,
                            'class_completed' => $m->class_completed,
                        ];
                    })->toArray(),
                ],
            ]);

            // Determinar la membresía actual basada en clases completadas efectivas
            // Ejemplo: Si tiene 102 clases efectivas y Rsistanc requiere 100, entonces tiene Rsistanc
            // Si tiene 107 clases efectivas y Gold requiere 107, entonces tiene Gold
            // IMPORTANTE: Esto determina la membresía basándose SOLO en clases completadas efectivas,
            // no en las membresías que el usuario ya tiene activas
            $currentMembershipByProgress = null;
            foreach ($allMemberships as $m) {
                // Si tiene suficientes clases efectivas para alcanzar esta membresía
                if ($totalCompletedClasses >= $m->class_completed) {
                    $currentMembershipByProgress = $m;
                } else {
                    break; // Ya encontramos la más alta que puede alcanzar
                }
            }
            
            // IMPORTANTE: Si el usuario alcanzó una membresía por progreso pero NO tiene una UserMembership activa,
            // debemos crear la UserMembership para esa membresía antes de buscar la siguiente
            if ($currentMembershipByProgress) {
                $hasCurrentMembershipActive = UserMembership::where('user_id', $userId)
                    ->where('membership_id', $currentMembershipByProgress->id)
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', now());
                    })
                    ->exists();
                
                // Si no tiene la membresía activa pero la alcanzó por progreso, crearla
                if (!$hasCurrentMembershipActive) {
                    // Obtener discipline_id (usar el parámetro o buscar en clases/membresías previas)
                    $disciplineIdForCurrent = $disciplineId;
                    
                    if (!$disciplineIdForCurrent) {
                        // Buscar en la última clase completada
                        $lastCompletedClass = \App\Models\ClassScheduleSeat::where('user_id', $userId)
                            ->where('status', 'Completed')
                            ->with(['classSchedule.class.discipline'])
                            ->orderBy('updated_at', 'desc')
                            ->first();
                        
                        if ($lastCompletedClass && $lastCompletedClass->classSchedule && $lastCompletedClass->classSchedule->class) {
                            $disciplineIdForCurrent = $lastCompletedClass->classSchedule->class->discipline_id;
                        }
                    }
                    
                    if (!$disciplineIdForCurrent) {
                        // Buscar en cualquier membresía previa del usuario
                        $anyUserMembership = UserMembership::where('user_id', $userId)
                            ->whereNotNull('discipline_id')
                            ->orderBy('created_at', 'desc')
                            ->first();
                        
                        if ($anyUserMembership) {
                            $disciplineIdForCurrent = $anyUserMembership->discipline_id;
                        }
                    }
                    
                    // Si tenemos discipline_id, crear la membresía actual
                    if ($disciplineIdForCurrent) {
                        $expiryDate = now()->addMonths($currentMembershipByProgress->duration ?? 3);
                        $disciplineQuantity = $currentMembershipByProgress->is_benefit_discipline
                            ? ($currentMembershipByProgress->discipline_quantity ?? 0)
                            : 0;
                        $shakeQuantity = $currentMembershipByProgress->is_benefit_shake
                            ? ($currentMembershipByProgress->shake_quantity ?? 0)
                            : 0;
                        
                        $newUserMembership = UserMembership::create([
                            'user_id' => $userId,
                            'membership_id' => $currentMembershipByProgress->id,
                            'discipline_id' => $disciplineIdForCurrent,
                            'total_free_classes' => $disciplineQuantity,
                            'used_free_classes' => 0,
                            'remaining_free_classes' => $disciplineQuantity,
                            'total_free_shakes' => $shakeQuantity,
                            'used_free_shakes' => 0,
                            'remaining_free_shakes' => $shakeQuantity,
                            'activation_date' => now(),
                            'expiry_date' => $expiryDate,
                            'status' => 'active',
                            'notes' => "Membresía otorgada automáticamente al alcanzar {$totalCompletedClasses} clases completadas (requisito: {$currentMembershipByProgress->class_completed} clases).",
                        ]);

                        // Actualizar los puntos del usuario con la nueva membresía activa
                        \App\Models\UserPoint::updateActiveMembershipForUser($userId, $currentMembershipByProgress->id);
                        
                        \App\Models\Log::create([
                            'user_id' => $userId,
                            'action' => 'Membresía actual creada automáticamente',
                            'description' => "✅ Se creó automáticamente la membresía '{$currentMembershipByProgress->name}' porque el usuario la alcanzó por progreso pero no tenía una UserMembership activa.",
                            'data' => [
                                'membership_id' => $currentMembershipByProgress->id,
                                'membership_name' => $currentMembershipByProgress->name,
                                'membership_level' => $currentMembershipByProgress->level,
                                'classes_completed' => $totalCompletedClasses,
                                'required_classes' => $currentMembershipByProgress->class_completed,
                                'user_membership_id' => $newUserMembership->id,
                                'discipline_id' => $disciplineIdForCurrent,
                            ],
                        ]);
                        
                        // IMPORTANTE: Después de crear la membresía actual, continuar para buscar la siguiente membresía
                        // porque el usuario puede haber alcanzado también la siguiente
                        // No retornar aquí, continuar con el flujo normal
                    } else {
                        // No se pudo determinar discipline_id para crear la membresía actual
                        \App\Models\Log::create([
                            'user_id' => $userId,
                            'action' => 'Error - No se pudo crear membresía actual',
                            'description' => "El usuario alcanzó {$currentMembershipByProgress->name} pero no se pudo determinar discipline_id para crear la UserMembership.",
                            'data' => [
                                'membership_id' => $currentMembershipByProgress->id,
                                'membership_name' => $currentMembershipByProgress->name,
                                'classes_completed' => $totalCompletedClasses,
                                'required_classes' => $currentMembershipByProgress->class_completed,
                            ],
                        ]);
                    }
                }
            }

            // Si no tiene membresía actual basada en progreso, no podemos determinar la siguiente
            if (!$currentMembershipByProgress) {
                $reason = 'No tiene membresía actual basada en progreso. El usuario tiene ' . $totalCompletedClasses . ' clases completadas, pero no alcanza ninguna membresía activa.';
                
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Evaluación de membresía - Sin membresía actual',
                    'description' => $reason,
                    'data' => [
                        'total_completed_classes' => $totalCompletedClasses,
                        'min_classes_required' => $allMemberships->min('class_completed') ?? 0,
                    ],
                ]);

                return [
                    'created' => false,
                    'reason' => $reason,
                    'total_completed_classes' => $totalCompletedClasses,
                    'debug' => [
                        'min_classes_required' => $allMemberships->min('class_completed') ?? 0,
                    ],
                ];
            }

            // Determinar la siguiente membresía
            // ESTRATEGIA: Buscar la siguiente membresía que requiera más clases que la actual,
            // ordenada por clases requeridas (ascendente) para obtener la siguiente inmediata
            // Ejemplo: Si tienes Rsistanc (100 clases), la siguiente es Gold (107 clases), no Black (300)
            
            $nextMembership = $allMemberships
                ->filter(function ($m) use ($currentMembershipByProgress) {
                    // Solo membresías que requieren MÁS clases que la actual
                    return $m->class_completed > $currentMembershipByProgress->class_completed;
                })
                ->sortBy('class_completed')  // Ordenar por clases requeridas (menor a mayor)
                ->first();  // Tomar la primera (la siguiente inmediata)
            
            // Log detallado de la búsqueda con todas las opciones disponibles
            if ($nextMembership) {
                // Obtener todas las membresías posibles para debug
                $allPossibleNext = $allMemberships
                    ->filter(function ($m) use ($currentMembershipByProgress) {
                        return $m->class_completed > $currentMembershipByProgress->class_completed;
                    })
                    ->sortBy('class_completed')
                    ->map(function ($m) {
                        return [
                            'name' => $m->name,
                            'level' => $m->level,
                            'classes_required' => $m->class_completed,
                        ];
                    })
                    ->values()
                    ->toArray();
                
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Evaluación de membresía - Siguiente membresía determinada',
                    'description' => "Membresía actual: {$currentMembershipByProgress->name} (requiere {$currentMembershipByProgress->class_completed} clases, nivel {$currentMembershipByProgress->level}). Siguiente seleccionada: {$nextMembership->name} (requiere {$nextMembership->class_completed} clases, nivel {$nextMembership->level})",
                    'data' => [
                        'current_membership' => [
                            'name' => $currentMembershipByProgress->name,
                            'level' => $currentMembershipByProgress->level,
                            'classes_required' => $currentMembershipByProgress->class_completed,
                        ],
                        'next_membership_selected' => [
                            'name' => $nextMembership->name,
                            'level' => $nextMembership->level,
                            'classes_required' => $nextMembership->class_completed,
                        ],
                        'classes_difference' => $nextMembership->class_completed - $currentMembershipByProgress->class_completed,
                        'total_completed_classes' => $totalCompletedClasses,
                        'all_possible_next_memberships' => $allPossibleNext,
                    ],
                ]);
            }

            // Si no hay siguiente membresía, el usuario ya tiene la más alta
            if (!$nextMembership) {
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Evaluación de membresía - Ya tiene la más alta',
                    'description' => "El usuario ya tiene la membresía más alta disponible: {$currentMembershipByProgress->name}",
                    'data' => [
                        'current_membership' => $currentMembershipByProgress->name,
                        'total_completed_classes' => $totalCompletedClasses,
                    ],
                ]);

                return [
                    'created' => false,
                    'reason' => 'Ya tiene la membresía más alta disponible',
                    'total_completed_classes' => $totalCompletedClasses,
                    'current_membership' => $currentMembershipByProgress->name,
                ];
            }

            // Calcular clases necesarias desde la membresía actual hacia la siguiente
            // Ejemplo: Si tiene Rsistanc (100) y Gold requiere 200, necesita 100 clases más
            // Si tiene 102 clases completadas, le faltan: 200 - 102 = 98 clases
            $classesNeeded = $nextMembership->class_completed - $totalCompletedClasses;
            $classesFromCurrent = $nextMembership->class_completed - $currentMembershipByProgress->class_completed;

            // Log: información de la siguiente membresía
            \App\Models\Log::create([
                'user_id' => $userId,
                'action' => 'Evaluación de membresía - Análisis de siguiente membresía',
                'description' => "Membresía actual: {$currentMembershipByProgress->name} (nivel {$currentMembershipByProgress->level}), Siguiente: {$nextMembership->name} (nivel {$nextMembership->level}). Clases completadas: {$totalCompletedClasses}, Requeridas para siguiente: {$nextMembership->class_completed}",
                'data' => [
                    'current_membership' => [
                        'id' => $currentMembershipByProgress->id,
                        'name' => $currentMembershipByProgress->name,
                        'level' => $currentMembershipByProgress->level,
                        'class_completed' => $currentMembershipByProgress->class_completed,
                    ],
                    'next_membership' => [
                        'id' => $nextMembership->id,
                        'name' => $nextMembership->name,
                        'level' => $nextMembership->level,
                        'class_completed' => $nextMembership->class_completed,
                    ],
                    'total_completed_classes' => $totalCompletedClasses,
                    'classes_needed' => $classesNeeded,
                ],
            ]);

            // Verificar si el usuario alcanzó el requisito para la siguiente membresía
            // Ejemplo: Si tiene 102 clases y Gold requiere 200, NO alcanzó (102 < 200)
            // Si tiene 200 o más clases, SÍ alcanzó (200 >= 200)
            // IMPORTANTE: Usamos >= para que si tiene EXACTAMENTE las clases requeridas, se cree la membresía
            if ($totalCompletedClasses >= $nextMembership->class_completed) {
                
                // Log detallado antes de verificar si ya tiene la membresía
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Evaluación de membresía - Verificando si alcanzó requisito',
                    'description' => "El usuario tiene {$totalCompletedClasses} clases completadas y la siguiente membresía requiere {$nextMembership->class_completed}. Condición: " . ($totalCompletedClasses >= $nextMembership->class_completed ? "CUMPLE" : "NO CUMPLE"),
                    'data' => [
                        'total_completed_classes' => $totalCompletedClasses,
                        'next_membership_required' => $nextMembership->class_completed,
                        'meets_requirement' => $totalCompletedClasses >= $nextMembership->class_completed,
                    ],
                ]);
                // Verificar si ya tiene esta membresía activa
                $hasNextMembership = UserMembership::where('user_id', $userId)
                    ->where('membership_id', $nextMembership->id)
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', now());
                    })
                    ->exists();

                if ($hasNextMembership) {
                    \App\Models\Log::create([
                        'user_id' => $userId,
                        'action' => 'Evaluación de membresía - Ya tiene la siguiente membresía activa',
                        'description' => "El usuario ya tiene la membresía {$nextMembership->name} activa",
                        'data' => [
                            'membership_id' => $nextMembership->id,
                            'membership_name' => $nextMembership->name,
                        ],
                    ]);

                    return [
                        'created' => false,
                        'reason' => 'Ya tiene esta membresía activa',
                        'total_completed_classes' => $totalCompletedClasses,
                        'current_membership' => $currentMembershipByProgress->name,
                        'next_membership' => $nextMembership->name,
                    ];
                }

                // Obtener la membresía activa del usuario (para obtener discipline_id y otros datos)
                $currentUserMembership = UserMembership::with(['membership'])
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', now());
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Si no hay disciplina proporcionada ni en la membresía activa, intentar obtenerla de la última clase completada
                if (!$disciplineId) {
                    $disciplineId = $currentUserMembership->discipline_id ?? null;
                    
                    // Si aún no tenemos discipline_id, buscar en la última clase completada del usuario
                    if (!$disciplineId) {
                        $lastCompletedClass = \App\Models\ClassScheduleSeat::where('user_id', $userId)
                            ->where('status', 'Completed')
                            ->with(['classSchedule.class.discipline'])
                            ->orderBy('updated_at', 'desc')
                            ->first();
                            
                        if ($lastCompletedClass && $lastCompletedClass->classSchedule && $lastCompletedClass->classSchedule->class) {
                            $disciplineId = $lastCompletedClass->classSchedule->class->discipline_id;
                            
                            \App\Models\Log::create([
                                'user_id' => $userId,
                                'action' => 'Disciplina obtenida de clase completada',
                                'description' => "Se obtuvo discipline_id ({$disciplineId}) de la última clase completada",
                                'data' => [
                                    'discipline_id' => $disciplineId,
                                    'class_schedule_id' => $lastCompletedClass->classSchedule->id ?? null,
                                ],
                            ]);
                        }
                    }
                }

                // Si aún no tenemos discipline_id, intentar obtenerlo de alguna membresía del usuario
                if (!$disciplineId) {
                    $anyUserMembership = UserMembership::where('user_id', $userId)
                        ->whereNotNull('discipline_id')
                        ->orderBy('created_at', 'desc')
                        ->first();
                        
                    if ($anyUserMembership) {
                        $disciplineId = $anyUserMembership->discipline_id;
                    }
                }

                // Validar que tenemos discipline_id antes de crear la membresía
                if (!$disciplineId) {
                    \App\Models\Log::create([
                        'user_id' => $userId,
                        'action' => 'Error - No se pudo determinar discipline_id',
                        'description' => "No se pudo determinar el discipline_id para crear la membresía. El usuario alcanzó el requisito pero no se puede crear la membresía sin disciplina.",
                        'data' => [
                            'total_completed_classes' => $totalCompletedClasses,
                            'next_membership' => $nextMembership->name,
                        ],
                    ]);

                    return [
                        'created' => false,
                        'reason' => 'No se pudo determinar la disciplina para la membresía. Verifique que el usuario tenga clases completadas o membresías previas.',
                        'total_completed_classes' => $totalCompletedClasses,
                        'current_membership' => $currentMembershipByProgress->name,
                        'next_membership' => $nextMembership->name,
                    ];
                }

                // Calcular fecha de expiración (duración de la membresía desde ahora)
                $expiryDate = now()->addMonths($nextMembership->duration ?? 3);

                // Obtener cantidad de clases gratis y shakes de la nueva membresía
                $disciplineQuantity = $nextMembership->is_benefit_discipline
                    ? ($nextMembership->discipline_quantity ?? 0)
                    : 0;
                $shakeQuantity = $nextMembership->is_benefit_shake
                    ? ($nextMembership->shake_quantity ?? 0)
                    : 0;

                $newUserMembership = UserMembership::create([
                    'user_id' => $userId,
                    'membership_id' => $nextMembership->id,
                    'discipline_id' => $disciplineId,
                    'total_free_classes' => $disciplineQuantity,
                    'used_free_classes' => 0,
                    'remaining_free_classes' => $disciplineQuantity,
                    'total_free_shakes' => $shakeQuantity,
                    'used_free_shakes' => 0,
                    'remaining_free_shakes' => $shakeQuantity,
                    'activation_date' => now(),
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                    'notes' => "Membresía otorgada automáticamente al alcanzar {$totalCompletedClasses} clases completadas (requisito: {$nextMembership->class_completed} clases).",
                ]);

                // Actualizar los puntos del usuario con la nueva membresía activa
                \App\Models\UserPoint::updateActiveMembershipForUser($userId, $nextMembership->id);

                // Registrar en el log
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Membresía automática creada',
                    'description' => "✅ Se creó automáticamente la membresía '{$nextMembership->name}' al alcanzar {$totalCompletedClasses} clases completadas (requisito: {$nextMembership->class_completed} clases).",
                    'data' => [
                        'membership_id' => $nextMembership->id,
                        'membership_name' => $nextMembership->name,
                        'membership_level' => $nextMembership->level,
                        'classes_completed' => $totalCompletedClasses,
                        'required_classes' => $nextMembership->class_completed,
                        'current_membership' => $currentMembershipByProgress->name ?? null,
                        'current_membership_level' => $currentMembershipByProgress->level ?? null,
                        'current_membership_required' => $currentMembershipByProgress->class_completed ?? null,
                        'classes_needed' => $classesNeeded,
                        'classes_from_current' => $classesFromCurrent,
                        'discipline_id' => $disciplineId,
                        'user_membership_id' => $newUserMembership->id,
                    ],
                ]);

                return [
                    'created' => true,
                    'user_membership_id' => $newUserMembership->id,
                    'membership_name' => $nextMembership->name,
                    'membership_level' => $nextMembership->level,
                    'total_completed_classes' => $totalCompletedClasses,
                    'required_classes' => $nextMembership->class_completed,
                    'current_membership' => $currentMembershipByProgress->name,
                    'discipline_id' => $disciplineId,
                ];
            } else {
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Evaluación de membresía - No alcanzó el requisito',
                    'description' => "El usuario no alcanzó el requisito para la siguiente membresía. Tiene {$totalCompletedClasses} clases, necesita {$nextMembership->class_completed} para {$nextMembership->name}. Le faltan {$classesNeeded} clases.",
                    'data' => [
                        'total_completed_classes' => $totalCompletedClasses,
                        'current_membership' => $currentMembershipByProgress->name,
                        'next_membership' => $nextMembership->name,
                        'classes_needed' => $classesNeeded,
                        'required_classes' => $nextMembership->class_completed,
                    ],
                ]);

                return [
                    'created' => false,
                    'reason' => 'No alcanzó el requisito para la siguiente membresía',
                    'total_completed_classes' => $totalCompletedClasses,
                    'current_membership' => $currentMembershipByProgress->name,
                    'next_membership' => $nextMembership->name,
                    'classes_needed' => $classesNeeded,
                    'required_classes' => $nextMembership->class_completed,
                ];
            }
        } catch (\Exception $e) {
            \App\Models\Log::create([
                'user_id' => $userId,
                'action' => 'Error al evaluar membresía automática',
                'description' => "❌ Error: " . $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            return [
                'created' => false,
                'reason' => 'Error: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Evalúa y crea membresías automáticamente para múltiples usuarios.
     *
     * @param array $userIds Array de IDs de usuarios
     * @param int|null $disciplineId ID de la disciplina (opcional, se obtiene de la clase si no se proporciona)
     * @return array Resumen de membresías creadas
     */
    public function evaluateAndCreateMembershipsForUsers(array $userIds, ?int $disciplineId = null): array
    {
        $results = [
            'total_users' => count($userIds),
            'memberships_created' => 0,
            'users_processed' => 0,
            'details' => [],
        ];

        foreach ($userIds as $userId) {
            $result = $this->evaluateAndCreateMembershipForUser($userId, $disciplineId);
            $results['users_processed']++;

            if ($result['created']) {
                $results['memberships_created']++;
            }

            $results['details'][] = [
                'user_id' => $userId,
                'result' => $result,
            ];
        }

        // Log resumen
        \App\Models\Log::create([
            'user_id' => null,
            'action' => 'Resumen evaluación de membresías',
            'description' => "Se evaluaron {$results['total_users']} usuarios. Membresías creadas: {$results['memberships_created']}",
            'data' => $results,
        ]);

        return $results;
    }
}

