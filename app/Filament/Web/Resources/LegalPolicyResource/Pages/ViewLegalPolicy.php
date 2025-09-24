<?php

namespace App\Filament\Web\Resources\LegalPolicyResource\Pages;

use App\Filament\Web\Resources\LegalPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLegalPolicy extends ViewRecord
{
    protected static string $resource = LegalPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Ver Política Legal';
    }
}