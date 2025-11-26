<?php

namespace App\Filament\Resources\JuiceCartCodesResource\Pages;

use App\Filament\Resources\JuiceCartCodesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJuiceCartCodes extends EditRecord
{
    protected static string $resource = JuiceCartCodesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
