<?php

namespace App\Filament\Web\Resources\FaqResource\Pages;

use App\Filament\Web\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva FAQ'),
        ];
    }

    public function getTitle(): string
    {
        return 'Preguntas Frecuentes';
    }
}