<?php

namespace App\Filament\Resources\FootwearLoanResource\Pages;

use App\Filament\Resources\FootwearLoanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFootwearLoan extends EditRecord
{
    protected static string $resource = FootwearLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
