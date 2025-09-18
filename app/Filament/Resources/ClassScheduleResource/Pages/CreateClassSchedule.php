<?php

namespace App\Filament\Resources\ClassScheduleResource\Pages;

use App\Filament\Resources\ClassScheduleResource;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateClassSchedule extends CreateRecord
{
    protected static string $resource = ClassScheduleResource::class;

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
