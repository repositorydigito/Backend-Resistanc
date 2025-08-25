<?php

namespace App\Filament\Resources\JuiceOrderResource\Pages;

use App\Filament\Resources\JuiceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJuiceOrder extends EditRecord
{
    protected static string $resource = JuiceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
