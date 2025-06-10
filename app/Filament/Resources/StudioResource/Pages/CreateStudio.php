<?php

namespace App\Filament\Resources\StudioResource\Pages;

use App\Filament\Resources\StudioResource;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateStudio extends CreateRecord
{
    protected static string $resource = StudioResource::class;

    protected function afterCreate(): void
    {
        // Contar asientos generados automáticamente
        $seatsGenerated = $this->record->seats()->count();

        if ($seatsGenerated > 0) {
            Notification::make()
                ->title('🎉 Estudio creado exitosamente')
                ->body("Se generaron {$seatsGenerated} asientos automáticamente basados en la configuración del estudio.")
                ->success()
                ->duration(5000)
                ->send();
        } else {
            Notification::make()
                ->title('⚠️ Estudio creado')
                ->body('El estudio se creó pero no se generaron asientos. Verifica la configuración de filas, columnas y capacidad.')
                ->warning()
                ->duration(5000)
                ->send();
        }
    }
}
