<?php

namespace App\Filament\Resources\StudioResource\Pages;

use App\Filament\Resources\StudioResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStudio extends EditRecord
{
    protected static string $resource = StudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Verificar si se modificaron campos que afectan la generación de asientos
        $seatingFields = ['row', 'column', 'addressing', 'capacity_per_seat'];
        $wasSeatingChanged = false;

        foreach ($seatingFields as $field) {
            if ($this->record->wasChanged($field)) {
                $wasSeatingChanged = true;
                break;
            }
        }

        if ($wasSeatingChanged) {
            // Contar asientos después de la regeneración
            $seatsGenerated = $this->record->seats()->count();

            if ($seatsGenerated > 0) {
                Notification::make()
                    ->title('🔄 Asientos regenerados')
                    ->body("Se regeneraron {$seatsGenerated} asientos automáticamente debido a los cambios en la configuración del estudio.")
                    ->success()
                    ->duration(5000)
                    ->send();
            } else {
                Notification::make()
                    ->title('⚠️ Configuración actualizada')
                    ->body('El estudio se actualizó pero no se generaron asientos. Verifica la configuración de filas, columnas y capacidad.')
                    ->warning()
                    ->duration(5000)
                    ->send();
            }
        }
    }
}
