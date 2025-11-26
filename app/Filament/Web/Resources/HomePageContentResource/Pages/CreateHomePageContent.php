<?php

namespace App\Filament\Web\Resources\HomePageContentResource\Pages;

use App\Filament\Web\Resources\HomePageContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomePageContent extends CreateRecord
{
    protected static string $resource = HomePageContentResource::class;

    public function getTitle(): string
    {
        return 'Crear Contenido';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}