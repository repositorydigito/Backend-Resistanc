<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Redirect;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Configuración')
                ->visible(fn() => Company::count() === 0),
        ];
    }

    public function mount(): void
    {
        // Si ya existe una empresa, redirigir directamente a editarla
        $company = Company::first();
        if ($company) {
            $this->redirect(EditCompany::getUrl(['record' => $company]));
        }
    }
}
