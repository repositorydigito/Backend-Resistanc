<?php

namespace App\Filament\Resources\FlavordrinkResource\Pages;

use App\Filament\Resources\FlavordrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFlavordrinks extends ListRecords
{
    protected static string $resource = FlavordrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
