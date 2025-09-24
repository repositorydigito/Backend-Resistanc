<?php

namespace App\Filament\Web\Resources\HomePageContentResource\Pages;

use App\Filament\Web\Resources\HomePageContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomePageContent extends EditRecord
{
    protected static string $resource = HomePageContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar Contenido';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}