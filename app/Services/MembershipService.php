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
     * @return array Información sobre si se creó una membresía
     */
    public function evaluateAndCreateMembershipForUser(int $userId): array
    {
        try {
            // Contar clases completadas del usuario
            $totalCompletedClasses = \App\Models\ClassScheduleSeat::where('user_id', $userId)
                ->where('status', 'Completed')
                ->count();

            // Obtener todas las membresías ordenadas por nivel
            $allMemberships = \App\Models\Membership::where('is_active', true)
                ->orderBy('level', 'asc')
                ->get();

            // Determinar la membresía actual basada en clases completadas
            // Ejemplo: Si tiene 102 clases y Rsistanc requiere 100, entonces tiene Rsistanc
            $currentMembershipByProgress = null;
            foreach ($allMemberships as $m) {
                if ($totalCompletedClasses >= $m->class_completed) {
                    $currentMembershipByProgress = $m;
                } else {
                    break; // Ya encontramos la más alta que puede alcanzar
                }
            }

            // Si no tiene membresía actual basada en progreso, no podemos determinar la siguiente
            if (!$currentMembershipByProgress) {
                return [
                    'created' => false,
                    'reason' => 'No tiene membresía actual basada en progreso',
                    'total_completed_classes' => $totalCompletedClasses,
                ];
            }

            // Determinar la siguiente membresía (level mayor que la actual)
            // Ejemplo: Si tiene Rsistanc (level 1), la siguiente es Gold (level 2)
            $nextMembership = $allMemberships
                ->filter(function ($m) use ($currentMembershipByProgress) {
                    return $m->level > $currentMembershipByProgress->level;
                })
                ->sortBy('level')
                ->first();

            // Si no hay siguiente membresía, el usuario ya tiene la más alta
            if (!$nextMembership) {
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

            // Verificar si el usuario alcanzó el requisito para la siguiente membresía
            // Ejemplo: Si tiene 102 clases y Gold requiere 200, NO alcanzó (102 < 200)
            // Si tiene 200 o más clases, SÍ alcanzó (200 >= 200)
            if ($totalCompletedClasses >= $nextMembership->class_completed) {
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

                // Crear la nueva membresía automáticamente
                $disciplineId = $currentUserMembership->discipline_id ?? null;

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

                // Registrar en el log
                \App\Models\Log::create([
                    'user_id' => $userId,
                    'action' => 'Membresía automática creada',
                    'description' => "Se creó automáticamente la membresía '{$nextMembership->name}' al alcanzar {$totalCompletedClasses} clases completadas (requisito: {$nextMembership->class_completed} clases).",
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
                ];
            } else {
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
            \Illuminate\Support\Facades\Log::error('Error al evaluar membresía automática para usuario', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * @return array Resumen de membresías creadas
     */
    public function evaluateAndCreateMembershipsForUsers(array $userIds): array
    {
        $results = [
            'total_users' => count($userIds),
            'memberships_created' => 0,
            'users_processed' => 0,
            'details' => [],
        ];

        foreach ($userIds as $userId) {
            $result = $this->evaluateAndCreateMembershipForUser($userId);
            $results['users_processed']++;

            if ($result['created']) {
                $results['memberships_created']++;
            }

            $results['details'][] = [
                'user_id' => $userId,
                'result' => $result,
            ];
        }

        return $results;
    }
}

