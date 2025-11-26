<?php

namespace App\Filament\Web\Resources\LegalFaqResource\Pages;

use App\Filament\Web\Resources\LegalFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLegalFaqs extends ListRecords
{
    protected static string $resource = LegalFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva FAQ Legal'),
        ];
    }

    public function getTitle(): string
    {
        return 'FAQs Legales';
    }
}