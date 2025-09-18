<?php

namespace App\Filament\Resources\StudioResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class SeatMapDirectRelationManager extends RelationManager
{
    protected static string $relationship = 'seats';

    protected static ?string $title = 'Mapa de Asientos';

    protected static ?string $modelLabel = 'Asiento';

    protected static ?string $pluralModelLabel = 'Asientos';

    protected static ?string $icon = 'heroicon-o-squares-2x2';

    /**
     * Verificar si un asiento está siendo utilizado en algún horario de clase
     */
    protected function isSeatInUse(Model $seat): bool
    {
        return $seat->seatAssignments()->exists();
    }

    /**
     * Obtener información sobre el uso del asiento
     */
    protected function getSeatUsageInfo(Model $seat): array
    {
        $assignments = $seat->seatAssignments()
            ->with(['classSchedule.class', 'classSchedule.studio'])
            ->get();

        $totalAssignments = $assignments->count();
        $activeAssignments = $assignments->whereIn('status', ['reserved', 'occupied'])->count();
        $completedAssignments = $assignments->where('status', 'Completed')->count();
        $availableAssignments = $assignments->where('status', 'available')->count();
        
        // Contar clases futuras usando una consulta separada
        $upcomingClasses = $seat->seatAssignments()
            ->whereHas('classSchedule', function ($query) {
                $query->where('scheduled_date', '>=', now()->toDateString());
            })
            ->count();

        $usageInfo = [
            'total_assignments' => $totalAssignments,
            'active_assignments' => $activeAssignments,
            'completed_assignments' => $completedAssignments,
            'available_assignments' => $availableAssignments,
            'upcoming_classes' => $upcomingClasses,
        ];

        return $usageInfo;
    }

    /**
     * Verificar si se puede eliminar un asiento
     */
    protected function canDeleteSeat(Model $seat): bool
    {
        return !$this->isSeatInUse($seat);
    }

    /**
     * Obtener mensaje de error para asientos que no se pueden eliminar
     */
    protected function getDeleteErrorMessage(Model $seat): string
    {
        $usageInfo = $this->getSeatUsageInfo($seat);
        
        $message = "No se puede eliminar este espacio porque está asignado a horarios de clase:\n";
        $message .= "• Total de asignaciones: {$usageInfo['total_assignments']}\n";
        $message .= "• Asignaciones activas: {$usageInfo['active_assignments']}\n";
        $message .= "• Asignaciones disponibles: {$usageInfo['available_assignments']}\n";
        $message .= "• Clases futuras: {$usageInfo['upcoming_classes']}";
        
        return $message;
    }

    public function render(): View
    {
        return view('filament.resources.studio-resource.seat-map-direct', [
            'studio' => $this->getOwnerRecord(),
            'relationManager' => $this,
        ]);
    }
}
