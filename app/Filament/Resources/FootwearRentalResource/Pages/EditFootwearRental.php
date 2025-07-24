<?php

namespace App\Filament\Resources\FootwearRentalResource\Pages;

use App\Filament\Resources\FootwearRentalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFootwearRental extends EditRecord
{
    protected static string $resource = FootwearRentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
