<?php

namespace App\Filament\Resources\FootwearResource\Pages;

use App\Filament\Resources\FootwearResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFootwear extends EditRecord
{
    protected static string $resource = FootwearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
