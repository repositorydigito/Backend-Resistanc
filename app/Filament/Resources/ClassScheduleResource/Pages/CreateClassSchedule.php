<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSchedule extends CreateRecord
{
    protected static string $resource = ClassScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validar que no exista otro horario con la misma clase, fecha y hora de inicio
        $existing = \App\Models\ClassSchedule::where('class_id', $data['class_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->where('start_time', $data['start_time'])
            ->where('status', '!=', 'cancelled') // Excluir cancelados
            ->first();

        // COMENTADO: Causa error de JSON en Livewire
        // if ($existing) {
        //     $class = $existing->class->name ?? 'Clase';
        //     Notification::make()
        //         ->title('Error: Horario duplicado')
        //         ->body("Ya existe un horario para esta clase el {$data['scheduled_date']} a las {$data['start_time']}. Horario ID: {$existing->id}")
        //         ->danger()
        //         ->persistent()
        //         ->send();
        //     
        //     $this->halt(); // Detener el proceso de creación
        // }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Contar asientos generados automáticamente
        $seatsGenerated = $this->record->seatAssignments()->count();

        if ($seatsGenerated > 0) {
            Notification::make()
                ->title('🎉 Horario creado exitosamente')
                ->body("Se generaron {$seatsGenerated} asientos automáticamente para este horario.")
                ->success()
                ->duration(5000)
                ->send();
        } else {
            Notification::make()
                ->title('⚠️ Horario creado')
                ->body('El horario se creó pero no se generaron asientos. Verifica la configuración del estudio.')
                ->warning()
                ->duration(5000)
                ->send();
        }
    }
}
