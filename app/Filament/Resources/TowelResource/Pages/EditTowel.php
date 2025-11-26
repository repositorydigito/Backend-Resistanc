<?php

namespace App\Filament\Resources\TowelResource\Pages;

use App\Filament\Resources\TowelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTowel extends EditRecord
{
    protected static string $resource = TowelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
