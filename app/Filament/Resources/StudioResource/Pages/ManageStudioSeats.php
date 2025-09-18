<?php

namespace App\Filament\Resources\StudioResource\Pages;

use App\Filament\Resources\StudioResource;
use App\Models\Studio;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class ManageStudioSeats extends Page
{
    protected static string $resource = StudioResource::class;

    protected static string $view = 'filament.resources.studio-resource.pages.manage-studio-seats';

    protected static ?string $title = 'Gestión de Asientos del Estudio';

    public Studio $record;

    public function mount(int|string $record): void
    {
        $this->record = Studio::findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return "Gestión de Asientos - {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerateSeats')
                ->label('Regenerar Asientos')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action('regenerateSeats')
                ->requiresConfirmation()
                ->modalHeading('Regenerar Asientos')
                ->modalDescription('Esto eliminará todos los asientos existentes y creará nuevos basados en la configuración actual. ¿Continuar?'),

            Actions\Action::make('backToStudio')
                ->label('Volver al Estudio')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => StudioResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function regenerateSeats(): void
    {
        try {
            // Contar asientos existentes antes de eliminar
            $deletedCount = $this->record->seats()->count();

            // Generar nuevos asientos (esto elimina los existentes automáticamente)
            $this->record->generateSeats();

            // Contar asientos después de la generación
            $generatedCount = $this->record->seats()->count();

            Notification::make()
                ->title('Asientos Regenerados')
                ->body("Se eliminaron {$deletedCount} asientos y se generaron {$generatedCount} nuevos asientos.")
                ->success()
                ->duration(5000)
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudieron regenerar los asientos: ' . $e->getMessage())
                ->danger()
                ->duration(8000)
                ->send();
        }
    }
}
