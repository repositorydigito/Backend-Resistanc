<?php

namespace App\Filament\Resources\ClassScheduleResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;

class SeatMapVisualRelationManager extends RelationManager
{
    protected static string $relationship = 'seatAssignments';

    protected static ?string $title = 'Mapa Visual de Asientos';

    protected static ?string $modelLabel = 'Asiento';

    protected static ?string $pluralModelLabel = 'Asientos';

    protected static ?string $icon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public function render(): View
    {
        return view('filament.resources.class-schedule-resource.relation-managers.seat-map-visual', [
            'schedule' => $this->getOwnerRecord(),
            'relationManager' => $this,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_seats')
                ->label('🔄 Regenerar Asientos')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerar Asientos del Horario')
                ->modalDescription('¿Estás seguro de que quieres regenerar todos los asientos? Esto eliminará todos los asientos existentes y creará nuevos basados en la configuración actual de la sala.')
                ->modalSubmitActionLabel('Sí, regenerar')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    try {
                        $schedule = $this->getOwnerRecord();
                        $createdCount = $schedule->regenerateSeats();

                        Notification::make()
                            ->title('Asientos regenerados exitosamente')
                            ->body("Se regeneraron {$createdCount} asientos para el horario.")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al regenerar asientos')
                            ->body('No se pudieron regenerar los asientos: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->getOwnerRecord()->studio_id !== null),
        ];
    }
}
