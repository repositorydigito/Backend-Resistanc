<?php

namespace App\Filament\Resources\VariantOptionResource\Pages;

use App\Filament\Resources\VariantOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVariantOption extends EditRecord
{
    protected static string $resource = VariantOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
