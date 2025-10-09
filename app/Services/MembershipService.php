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
}

