<?php

namespace App\Filament\Resources\JuiceOrderResource\Pages;

use App\Filament\Resources\JuiceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJuiceOrders extends ListRecords
{
    protected static string $resource = JuiceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
