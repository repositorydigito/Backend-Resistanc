<?php

namespace App\Filament\Resources\FootwearRentalResource\Pages;

use App\Filament\Resources\FootwearRentalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFootwearRentals extends ListRecords
{
    protected static string $resource = FootwearRentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
