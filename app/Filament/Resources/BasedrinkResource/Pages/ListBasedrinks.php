<?php

namespace App\Filament\Resources\BasedrinkResource\Pages;

use App\Filament\Resources\BasedrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasedrinks extends ListRecords
{
    protected static string $resource = BasedrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
