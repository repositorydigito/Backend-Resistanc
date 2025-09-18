<?php

namespace App\Filament\Resources\TowelLoanResource\Pages;

use App\Filament\Resources\TowelLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTowelLoan extends EditRecord
{
    protected static string $resource = TowelLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
