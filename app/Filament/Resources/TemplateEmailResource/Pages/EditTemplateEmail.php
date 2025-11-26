<?php

namespace App\Filament\Resources\TemplateEmailResource\Pages;

use App\Filament\Resources\TemplateEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplateEmail extends EditRecord
{
    protected static string $resource = TemplateEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}
