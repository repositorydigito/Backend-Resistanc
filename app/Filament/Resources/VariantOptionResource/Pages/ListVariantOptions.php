<?php

namespace App\Filament\Resources\VariantOptionResource\Pages;

use App\Filament\Resources\VariantOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVariantOptions extends ListRecords
{
    protected static string $resource = VariantOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
