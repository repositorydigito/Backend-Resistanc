<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserPackage;
use App\Models\UserMembership;
use App\Models\ClassSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio para validar disponibilidad y compatibilidad de paquetes
 */
final class PackageValidationService
{
    /**
     * Valida si el usuario tiene paquetes disponibles para la disciplina de la clase
     *
     * @param ClassSchedule $classSchedule
     * @param int|null $userId
     * @return array
     */
    public function validateUserPackagesForSchedule(ClassSchedule $classSchedule, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return [
                'valid' => false,
                'message' => 'Usuario no autenticado',
                'available_packages' => [],
                'discipline_required' => null
            ];
        }

        // Cargar la clase con su disciplina
        $classSchedule->load(['class.discipline']);
        $disciplineId = $classSchedule->class->discipline_id ?? null;
        $disciplineName = $classSchedule->class->discipline->name ?? 'Desconocida';

        if (!$disciplineId) {
            return [
                'valid' => false,
                'message' => 'La clase no tiene una disciplina asignada',
                'available_packages' => [],
                'discipline_required' => null
            ];
        }

        // Obtener paquetes válidos del usuario para esta disciplina
        $availablePackages = $this->getUserAvailablePackagesForDiscipline($userId, $disciplineId);

        // Obtener membresías con clases gratis disponibles para esta disciplina
        $availableMemberships = $this->getUserAvailableMembershipsForDiscipline($userId, $disciplineId);

        // 🎯 Obtener membresías adicionales cuya disciplina está en el grupo de disciplinas de los paquetes del usuario
        // Esto permite usar una membresía de "Pilates" para consumir "Cycling" si ambos están en el mismo grupo del paquete
        $membershipsFromPackageGroups = $this->getUserMembershipsFromPackageDisciplineGroups($userId, $disciplineId);
        
        // Combinar ambas colecciones (membresías directas + membresías del grupo de paquetes)
        $availableMemberships = $availableMemberships->merge($membershipsFromPackageGroups)->unique('id');

        // 🎯 También buscar paquetes que incluyan esta disciplina a través de paquetes con múltiples disciplinas
        // Esto asegura que si un usuario tiene un paquete con múltiples disciplinas que incluye esta, se encuentre
        // Esto es una verificación adicional porque whereHas('package.disciplines') debería funcionar, pero por si acaso
        $allUserPackages = UserPackage::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('remaining_classes', '>', 0)
            ->whereHas('package', function ($query) {
                $query->where('status', 'active');
            })
            ->whereDate('expiry_date', '>=', now())
            ->with(['package.disciplines:id,name'])
            ->get();

        // Si hay paquetes que incluyen esta disciplina en su grupo, agregarlos
        $packagesWithDiscipline = $allUserPackages->filter(function ($userPackage) use ($disciplineId) {
            if (!$userPackage->package || !$userPackage->package->disciplines || $userPackage->package->disciplines->isEmpty()) {
                return false;
            }
            $packageDisciplineIds = $userPackage->package->disciplines->pluck('id')->toArray();
            $hasDiscipline = in_array($disciplineId, $packageDisciplineIds);
            
            // Log para debugging
            if ($hasDiscipline) {
                \Illuminate\Support\Facades\Log::info('Paquete encontrado con disciplina en grupo', [
                    'user_package_id' => $userPackage->id,
                    'package_id' => $userPackage->package_id,
                    'package_name' => $userPackage->package->name ?? 'N/A',
                    'required_discipline_id' => $disciplineId,
                    'package_disciplines' => $packageDisciplineIds,
                ]);
            }
            
            return $hasDiscipline;
        });

        // Combinar paquetes encontrados (los que ya encontramos + los del grupo)
        // Eliminar duplicados comparando por ID
        if ($packagesWithDiscipline->isNotEmpty()) {
            $existingPackageIds = $availablePackages->pluck('id')->toArray();
            $newPackages = $packagesWithDiscipline->reject(function ($package) use ($existingPackageIds) {
                return in_array($package->id, $existingPackageIds);
            });
            $availablePackages = $availablePackages->merge($newPackages);
        }

        // Si no hay paquetes ni membresías disponibles
        if ($availablePackages->isEmpty() && $availableMemberships->isEmpty()) {
            return [
                'valid' => false,
                'message' => "No tienes paquetes ni clases gratis disponibles para la disciplina '{$disciplineName}'",
                'available_packages' => [],
                'available_memberships' => [],
                'discipline_required' => [
                    'id' => $disciplineId,
                    'name' => $disciplineName
                ]
            ];
        }

        return [
            'valid' => true,
            'message' => 'Paquetes y/o membresías disponibles encontrados',
            'available_packages' => $availablePackages->map(function ($userPackage) {
                // Cargar todas las disciplinas del paquete si no están cargadas
                if (!$userPackage->relationLoaded('package.disciplines')) {
                    $userPackage->load('package.disciplines');
                }

                $disciplines = $userPackage->package->disciplines ?? collect();

                return [
                    'id' => $userPackage->id,
                    'package_code' => $userPackage->package_code,
                    'package_name' => $userPackage->package->name ?? 'N/A',
                    'remaining_classes' => $userPackage->remaining_classes,
                    'expiry_date' => $userPackage->expiry_date?->toDateString(),
                    'days_remaining' => $userPackage->days_remaining,
                    'type' => 'package',
                    'disciplines' => $disciplines->map(function ($discipline) {
                        return [
                            'id' => $discipline->id,
                            'name' => $discipline->name,
                        ];
                    })->toArray(),
                    'disciplines_count' => $disciplines->count(),
                    'is_multi_discipline' => $disciplines->count() > 1,
                    'discipline_names' => $disciplines->pluck('name')->toArray(),
                ];
            })->toArray(),
            'available_memberships' => $availableMemberships->map(function ($membership) {
                return [
                    'id' => $membership->id,
                    'membership_name' => $membership->membership->name ?? 'N/A',
                    'discipline_name' => $membership->discipline->name ?? 'N/A',
                    'remaining_free_classes' => $membership->remaining_free_classes,
                    'expiry_date' => $membership->expiry_date?->toDateString(),
                    'days_remaining' => $membership->days_remaining,
                    'type' => 'membership'
                ];
            })->toArray(),
            'discipline_required' => [
                'id' => $disciplineId,
                'name' => $disciplineName
            ]
        ];
    }

    /**
     * Obtiene los paquetes disponibles del usuario para una disciplina específica
     *
     * @param int $userId
     * @param int $disciplineId
     * @return Collection<UserPackage>
     */
    public function getUserAvailablePackagesForDiscipline(int $userId, int $disciplineId): Collection
    {
        return UserPackage::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('remaining_classes', '>', 0)
            ->whereHas('package', function ($query) {
                $query->where('status', 'active');
            })
            ->whereHas('package.disciplines', function ($query) use ($disciplineId) {
                $query->where('disciplines.id', $disciplineId);
            })
            ->whereDate('expiry_date', '>=', now())
            ->with(['package:id,name', 'package.disciplines:id,name'])
            ->orderBy('expiry_date', 'asc') // Usar primero los que expiran antes
            ->get();
    }

    /**
     * Obtiene las membresías con clases gratis disponibles del usuario para una disciplina específica
     *
     * @param int $userId
     * @param int $disciplineId
     * @return Collection<UserMembership>
     */
    public function getUserAvailableMembershipsForDiscipline(int $userId, int $disciplineId): Collection
    {
        return UserMembership::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('remaining_free_classes', '>', 0)
            ->where('discipline_id', $disciplineId)
            ->whereDate('expiry_date', '>=', now())
            ->with(['membership:id,name', 'discipline:id,name'])
            ->orderBy('expiry_date', 'asc') // Usar primero los que expiran antes
            ->get();
    }

    /**
     * Obtiene membresías del usuario cuya disciplina está en el grupo de disciplinas de algún paquete del sistema
     * que también incluye la disciplina requerida. Esto permite usar membresías de otras disciplinas si están en el mismo
     * grupo de disciplinas, similar a como lo hace HomeController.
     * 
     * Ejemplo: Si el usuario tiene una membresía de "Pilates" y existe un paquete con grupo ["Cycling", "Pilates", "Box"],
     * puede usar esa membresía para consumir clases de "Cycling" porque están en el mismo grupo.
     *
     * @param int $userId
     * @param int $requiredDisciplineId Disciplina requerida para la clase
     * @return Collection<UserMembership>
     */
    public function getUserMembershipsFromPackageDisciplineGroups(int $userId, int $requiredDisciplineId): Collection
    {
        // 🎯 Obtener TODOS los paquetes activos del SISTEMA con sus disciplinas (como hace HomeController)
        // Esto permite ver todos los grupos posibles de disciplinas
        $allPackages = \App\Models\Package::query()
            ->with(['disciplines:id,name'])
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

        if ($allPackages->isEmpty()) {
            return collect();
        }

        // 🎯 Encontrar grupos de disciplinas que incluyen la disciplina requerida
        $validGroupKeys = [];
        foreach ($allPackages as $package) {
            if (!$package->disciplines || $package->disciplines->isEmpty()) {
                continue;
            }
            
            $packageDisciplineIds = $package->disciplines->pluck('id')->sort()->values()->toArray();
            
            // Si el paquete incluye la disciplina requerida, este es un grupo válido
            if (in_array($requiredDisciplineId, $packageDisciplineIds)) {
                $groupKey = implode('-', $packageDisciplineIds);
                if (!in_array($groupKey, $validGroupKeys)) {
                    $validGroupKeys[] = $groupKey;
                }
            }
        }

        if (empty($validGroupKeys)) {
            return collect();
        }

        // 🎯 Obtener todas las membresías activas del usuario
        $userMemberships = UserMembership::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('remaining_free_classes', '>', 0)
            ->whereDate('expiry_date', '>=', now())
            ->with(['membership:id,name', 'discipline:id,name', 'sourcePackage:id,name'])
            ->orderBy('expiry_date', 'asc')
            ->get();

        if ($userMemberships->isEmpty()) {
            return collect();
        }

        // 🎯 Filtrar membresías cuya disciplina está en alguno de los grupos válidos (que incluyen la disciplina requerida)
        $validMemberships = $userMemberships->filter(function ($membership) use ($allPackages, $validGroupKeys, $requiredDisciplineId) {
            if (!$membership->discipline) {
                return false;
            }

            $membershipDisciplineId = $membership->discipline_id;

            // Verificar si la disciplina de la membresía está en algún grupo válido
            foreach ($allPackages as $package) {
                if (!$package->disciplines || $package->disciplines->isEmpty()) {
                    continue;
                }

                $packageDisciplineIds = $package->disciplines->pluck('id')->sort()->values()->toArray();
                $groupKey = implode('-', $packageDisciplineIds);

                // Si este grupo es válido (incluye la disciplina requerida) y también incluye la disciplina de la membresía
                if (in_array($groupKey, $validGroupKeys) && 
                    in_array($membershipDisciplineId, $packageDisciplineIds)) {
                    
                    \Illuminate\Support\Facades\Log::info('Membresía válida encontrada en grupo de disciplinas', [
                        'membership_id' => $membership->id,
                        'membership_discipline_id' => $membershipDisciplineId,
                        'membership_discipline_name' => $membership->discipline->name ?? 'N/A',
                        'required_discipline_id' => $requiredDisciplineId,
                        'package_id' => $package->id,
                        'package_name' => $package->name ?? 'N/A',
                        'group_key' => $groupKey,
                        'package_disciplines' => $packageDisciplineIds,
                    ]);
                    
                    return true;
                }
            }
            
            return false;
        });

        return $validMemberships->values();
    }

    /**
     * Consume una clase del mejor paquete disponible para la disciplina
     *
     * @param int $userId
     * @param int $disciplineId
     * @return array
     */
    public function consumeClassFromPackage(int $userId, int $disciplineId): array
    {
        $availablePackages = $this->getUserAvailablePackagesForDiscipline($userId, $disciplineId);

        if ($availablePackages->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No hay paquetes disponibles para consumir',
                'consumed_package' => null
            ];
        }

        // Usar el primer paquete (el que expira antes)
        $packageToUse = $availablePackages->first();

        if (!$packageToUse->useClasses(1)) {
            return [
                'success' => false,
                'message' => 'No se pudo consumir la clase del paquete',
                'consumed_package' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Clase consumida exitosamente',
            'consumed_package' => [
                'id' => $packageToUse->id,
                'package_code' => $packageToUse->package_code,
                'package_name' => $packageToUse->package->name ?? 'N/A',
                'classes_consumed' => 1,
                'remaining_classes' => $packageToUse->remaining_classes,
                'used_classes' => $packageToUse->used_classes,
                'type' => 'package'
            ]
        ];
    }

    /**
     * Consume una clase gratis de la mejor membresía disponible para la disciplina
     *
     * @param int $userId
     * @param int $disciplineId
     * @return array
     */
    public function consumeClassFromMembership(int $userId, int $disciplineId): array
    {
        // Obtener membresías directas para la disciplina
        $availableMemberships = $this->getUserAvailableMembershipsForDiscipline($userId, $disciplineId);

        // 🎯 Obtener membresías adicionales cuya disciplina está en el grupo de disciplinas de los paquetes del usuario
        $membershipsFromPackageGroups = $this->getUserMembershipsFromPackageDisciplineGroups($userId, $disciplineId);
        
        // Combinar ambas colecciones (membresías directas + membresías del grupo de paquetes)
        $availableMemberships = $availableMemberships->merge($membershipsFromPackageGroups)->unique('id');

        if ($availableMemberships->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No hay membresías con clases gratis disponibles para consumir',
                'consumed_membership' => null
            ];
        }

        // Usar la primera membresía (la que expira antes)
        $membershipToUse = $availableMemberships->first();

        if (!$membershipToUse->useFreeClasses(1)) {
            return [
                'success' => false,
                'message' => 'No se pudo consumir la clase gratis de la membresía',
                'consumed_membership' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Clase gratis consumida exitosamente',
            'consumed_membership' => [
                'id' => $membershipToUse->id,
                'membership_name' => $membershipToUse->membership->name ?? 'N/A',
                'discipline_name' => $membershipToUse->discipline->name ?? 'N/A',
                'classes_consumed' => 1,
                'remaining_free_classes' => $membershipToUse->remaining_free_classes,
                'used_free_classes' => $membershipToUse->used_free_classes,
                'type' => 'membership'
            ]
        ];
    }

    /**
     * Consume una clase priorizando membresías sobre paquetes (membresías primero)
     *
     * @param int $userId
     * @param int $disciplineId
     * @return array
     */
    public function consumeClassFromBestOption(int $userId, int $disciplineId): array
    {
        // Primero intentar con membresías (clases gratis)
        $membershipResult = $this->consumeClassFromMembership($userId, $disciplineId);

        if ($membershipResult['success']) {
            return $membershipResult;
        }

        // Si no hay membresías disponibles, usar paquetes
        return $this->consumeClassFromPackage($userId, $disciplineId);
    }

    /**
     * Reembolsa una clase a un paquete específico
     *
     * @param int $userPackageId
     * @param int $userId
     * @return array
     */
    public function refundClassToPackage(int $userPackageId, int $userId): array
    {
        $userPackage = UserPackage::where('id', $userPackageId)
            ->where('user_id', $userId)
            ->first();

        if (!$userPackage) {
            return [
                'success' => false,
                'message' => 'Paquete no encontrado o no pertenece al usuario',
                'refunded_package' => null
            ];
        }

        if (!$userPackage->refundClasses(1)) {
            return [
                'success' => false,
                'message' => 'No se pudo reembolsar la clase al paquete',
                'refunded_package' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Clase reembolsada exitosamente',
            'refunded_package' => [
                'id' => $userPackage->id,
                'package_code' => $userPackage->package_code,
                'package_name' => $userPackage->package->name ?? 'N/A',
                'classes_refunded' => 1,
                'remaining_classes' => $userPackage->remaining_classes,
                'used_classes' => $userPackage->used_classes
            ]
        ];
    }

    /**
     * Obtiene un resumen de paquetes del usuario por disciplina
     *
     * @param int $userId
     * @return array
     */
    public function getUserPackagesSummaryByDiscipline(int $userId): array
    {
        $userPackages = UserPackage::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('remaining_classes', '>', 0)
            ->whereDate('expiry_date', '>=', now())
            ->with(['package.disciplines:id,name'])
            ->get();

        $summary = [];

        foreach ($userPackages as $userPackage) {
            // Ahora un paquete puede tener múltiples disciplinas
            $disciplines = $userPackage->package->disciplines ?? collect();

            if ($disciplines->isEmpty()) {
                // Paquete sin disciplinas asignadas
                $disciplineId = null;
                $disciplineName = 'Sin disciplina';

                if (!isset($summary[$disciplineId])) {
                    $summary[$disciplineId] = [
                        'discipline_id' => $disciplineId,
                        'discipline_name' => $disciplineName,
                        'total_packages' => 0,
                        'total_classes_remaining' => 0,
                        'packages' => []
                    ];
                }

                $summary[$disciplineId]['total_packages']++;
                $summary[$disciplineId]['total_classes_remaining'] += $userPackage->remaining_classes;
                $summary[$disciplineId]['packages'][] = [
                    'id' => $userPackage->id,
                    'package_code' => $userPackage->package_code,
                    'package_name' => $userPackage->package->name ?? 'N/A',
                    'remaining_classes' => $userPackage->remaining_classes,
                    'expiry_date' => $userPackage->expiry_date?->toDateString(),
                    'disciplines' => []
                ];
            } else {
                // Agrupar por cada disciplina que tenga el paquete
                foreach ($disciplines as $discipline) {
                    $disciplineId = $discipline->id;
                    $disciplineName = $discipline->name;

                    if (!isset($summary[$disciplineId])) {
                        $summary[$disciplineId] = [
                            'discipline_id' => $disciplineId,
                            'discipline_name' => $disciplineName,
                            'total_packages' => 0,
                            'total_classes_remaining' => 0,
                            'packages' => []
                        ];
                    }

                    // Verificar si este paquete ya está en la lista de esta disciplina
                    $packageExists = collect($summary[$disciplineId]['packages'])->contains('id', $userPackage->id);

                    if (!$packageExists) {
                        $summary[$disciplineId]['total_packages']++;
                        $summary[$disciplineId]['total_classes_remaining'] += $userPackage->remaining_classes;
                        $summary[$disciplineId]['packages'][] = [
                            'id' => $userPackage->id,
                            'package_code' => $userPackage->package_code,
                            'package_name' => $userPackage->package->name ?? 'N/A',
                            'remaining_classes' => $userPackage->remaining_classes,
                            'expiry_date' => $userPackage->expiry_date?->toDateString(),
                            'disciplines' => $disciplines->pluck('name')->toArray()
                        ];
                    }
                }
            }
        }

        return array_values($summary);
    }
}
