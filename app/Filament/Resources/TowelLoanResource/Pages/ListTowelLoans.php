<?php

namespace App\Filament\Resources\TowelLoanResource\Pages;

use App\Filament\Resources\TowelLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTowelLoans extends ListRecords
{
    protected static string $resource = TowelLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
