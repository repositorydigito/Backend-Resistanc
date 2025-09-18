<?php

namespace App\Filament\Resources\FlavordrinkResource\Pages;

use App\Filament\Resources\FlavordrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFlavordrink extends EditRecord
{
    protected static string $resource = FlavordrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
