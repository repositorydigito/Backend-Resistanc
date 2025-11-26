<?php

namespace App\Filament\Web\Resources\LegalPolicyResource\Pages;

use App\Filament\Web\Resources\LegalPolicyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLegalPolicy extends CreateRecord
{
    protected static string $resource = LegalPolicyResource::class;

    public function getTitle(): string
    {
        return 'Crear Política Legal';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}