<?php

namespace App\Filament\Resources\PromoCodesResource\Pages;

use App\Filament\Resources\PromoCodesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromoCodes extends EditRecord
{
    protected static string $resource = PromoCodesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
