<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditClassSchedule extends EditRecord
{
    protected static string $resource = ClassScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageSeats')
                ->label('Gestionar Asientos')
                ->icon('heroicon-o-squares-plus')
                ->color('info')
                ->url(fn () => static::$resource::getUrl('manage-seats', ['record' => $this->record])),

            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Verificar si se cambió la sala
        if ($this->record->wasChanged('studio_id')) {
            $seatsGenerated = $this->record->seatAssignments()->count();

            Notification::make()
                ->title('🔄 Sala actualizada')
                ->body("Se regeneraron {$seatsGenerated} asientos para la nueva sala.")
                ->success()
                ->duration(5000)
                ->send();
        }

        // Emitir evento JavaScript para actualizar componentes Livewire
        $this->dispatch('schedule-updated', scheduleId: $this->record->id);
    }
}
