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
            // Actions\Action::make('manageSeats')
            //     ->label('Gestionar Asientos')
            //     ->icon('heroicon-o-squares-plus')
            //     ->color('info')
            //     ->url(fn () => static::$resource::getUrl('manage-seats', ['record' => $this->record])),

            Actions\DeleteAction::make(),


        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validar que no exista otro horario con la misma clase, fecha y hora de inicio
        // Excluir el registro actual
        $existing = \App\Models\ClassSchedule::where('class_id', $data['class_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->where('start_time', $data['start_time'])
            ->where('status', '!=', 'cancelled') // Excluir cancelados
            ->where('id', '!=', $this->record->id) // Excluir el registro actual
            ->first();

        // COMENTADO: Causa error de JSON en Livewire
        // if ($existing) {
        //     $class = $existing->class->name ?? 'Clase';
        //     Notification::make()
        //         ->title('Error: Horario duplicado')
        //         ->body("Ya existe otro horario para esta clase el {$data['scheduled_date']} a las {$data['start_time']}. Horario ID: {$existing->id}")
        //         ->danger()
        //         ->persistent()
        //         ->send();
        //     
        //     $this->halt(); // Detener el proceso de actualización
        // }

        return $data;
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
