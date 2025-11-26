<?php

namespace App\Filament\Resources\TypedrinkResource\Pages;

use App\Filament\Resources\TypedrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypedrink extends EditRecord
{
    protected static string $resource = TypedrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
