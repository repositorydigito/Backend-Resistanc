<?php

namespace App\Filament\Resources\ProductOptionTypeResource\Pages;

use App\Filament\Resources\ProductOptionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductOptionTypes extends ListRecords
{
    protected static string $resource = ProductOptionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
