<?php

namespace App\Filament\Resources\TowelRentalResource\Pages;

use App\Filament\Resources\TowelRentalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTowelRental extends EditRecord
{
    protected static string $resource = TowelRentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
