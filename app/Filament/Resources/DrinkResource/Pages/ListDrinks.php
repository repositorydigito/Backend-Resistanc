<?php

namespace App\Filament\Resources\DrinkResource\Pages;

use App\Filament\Resources\DrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDrinks extends ListRecords
{
    protected static string $resource = DrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
