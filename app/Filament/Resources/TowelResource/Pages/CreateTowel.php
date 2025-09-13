<?php

namespace App\Filament\Resources\TowelResource\Pages;

use App\Filament\Resources\TowelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTowel extends CreateRecord
{
    protected static string $resource = TowelResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction()
                ->visible(false),
            $this->getCancelFormAction(),
        ];
    }
}
