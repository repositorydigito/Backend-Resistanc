<?php

namespace App\Filament\Web\Resources\DisciplineResource\Pages;

use App\Filament\Web\Resources\DisciplineResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscipline extends CreateRecord
{
    protected static string $resource = DisciplineResource::class;

    public function getTitle(): string
    {
        return 'Crear Disciplina';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}