<?php

namespace App\Filament\Web\Resources\LegalPolicyResource\Pages;

use App\Filament\Web\Resources\LegalPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLegalPolicies extends ListRecords
{
    protected static string $resource = LegalPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Política'),
        ];
    }

    public function getTitle(): string
    {
        return 'Políticas Legales';
    }
}