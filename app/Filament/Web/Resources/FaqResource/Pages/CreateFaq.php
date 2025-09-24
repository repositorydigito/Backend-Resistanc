<?php

namespace App\Filament\Web\Resources\FaqResource\Pages;

use App\Filament\Web\Resources\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

    public function getTitle(): string
    {
        return 'Crear Pregunta Frecuente';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}