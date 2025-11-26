<?php

namespace App\Filament\Web\Resources\ServiceResource\Pages;

use App\Filament\Web\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return 'Crear Servicio';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}