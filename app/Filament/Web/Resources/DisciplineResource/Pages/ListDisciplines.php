<?php

namespace App\Filament\Web\Resources\DisciplineResource\Pages;

use App\Filament\Web\Resources\DisciplineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDisciplines extends ListRecords
{
    protected static string $resource = DisciplineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Disciplina'),
        ];
    }

    public function getTitle(): string
    {
        return 'Disciplinas';
    }
}