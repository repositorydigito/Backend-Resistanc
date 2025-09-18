<?php

namespace App\Filament\Resources\TowelResource\Pages;

use App\Filament\Resources\TowelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTowels extends ListRecords
{
    protected static string $resource = TowelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
