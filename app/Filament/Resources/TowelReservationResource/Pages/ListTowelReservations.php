<?php

namespace App\Filament\Resources\TowelReservationResource\Pages;

use App\Filament\Resources\TowelReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTowelReservations extends ListRecords
{
    protected static string $resource = TowelReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
