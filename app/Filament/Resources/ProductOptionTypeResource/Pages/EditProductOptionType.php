<?php

namespace App\Filament\Resources\ProductOptionTypeResource\Pages;

use App\Filament\Resources\ProductOptionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductOptionType extends EditRecord
{
    protected static string $resource = ProductOptionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
