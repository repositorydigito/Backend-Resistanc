<?php

namespace App\Filament\Resources\TypedrinkResource\Pages;

use App\Filament\Resources\TypedrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypedrinks extends ListRecords
{
    protected static string $resource = TypedrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
