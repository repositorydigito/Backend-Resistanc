<?php

namespace App\Filament\Resources\TowelReservationResource\Pages;

use App\Filament\Resources\TowelReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTowelReservation extends EditRecord
{
    protected static string $resource = TowelReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
