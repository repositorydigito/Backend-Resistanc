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
                return [
                    'id' => $userPackage->id,
                    'package_code' => $userPackage->package_code,
                    'package_name' => $userPackage->package->name ?? 'N/A',
                    'remaining_classes' => $userPackage->remaining_classes,
                    'expiry_date' => $userPackage->expiry_date?->toDateString(),
                    'days_remaining' => $userPackage->days_remaining,
                    'type' => 'package'
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
            ->whereHas('package', function ($query) use ($disciplineId) {
                $query->where('discipline_id', $disciplineId)
                      ->where('status', 'active');
            })
            ->whereDate('expiry_date', '>=', now())
            ->with(['package:id,name,discipline_id'])
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
        $availableMemberships = $this->getUserAvailableMembershipsForDiscipline($userId, $disciplineId);

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
            ->with(['package.discipline:id,name'])
            ->get();

        $summary = [];

        foreach ($userPackages as $userPackage) {
            $disciplineId = $userPackage->package->discipline_id ?? null;
            $disciplineName = $userPackage->package->discipline->name ?? 'Sin disciplina';

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
                'expiry_date' => $userPackage->expiry_date?->toDateString()
            ];
        }

        return array_values($summary);
    }
}
