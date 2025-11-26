<?php

namespace App\Filament\Web\Resources\LegalFaqResource\Pages;

use App\Filament\Web\Resources\LegalFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLegalFaq extends EditRecord
{
    protected static string $resource = LegalFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar FAQ Legal';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}