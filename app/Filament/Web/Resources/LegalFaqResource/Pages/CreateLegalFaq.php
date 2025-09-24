<?php

namespace App\Filament\Web\Resources\LegalFaqResource\Pages;

use App\Filament\Web\Resources\LegalFaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLegalFaq extends CreateRecord
{
    protected static string $resource = LegalFaqResource::class;

    public function getTitle(): string
    {
        return 'Crear FAQ Legal';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}