<?php

namespace App\Filament\Resources\BasedrinkResource\Pages;

use App\Filament\Resources\BasedrinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBasedrink extends EditRecord
{
    protected static string $resource = BasedrinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
