<?php

namespace App\Filament\Web\Resources\HomePageContentResource\Pages;

use App\Filament\Web\Resources\HomePageContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomePageContents extends ListRecords
{
    protected static string $resource = HomePageContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Contenido'),
        ];
    }

    public function getTitle(): string
    {
        return 'Contenido de Página de Inicio';
    }
}