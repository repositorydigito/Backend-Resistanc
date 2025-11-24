<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Filament\Resources\PackageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction()
                ->visible(false),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convertir precio CON IGV a precio SIN IGV para guardar en la base de datos
        if (isset($data['price_with_igv']) && $data['price_with_igv'] > 0) {
            $igv = (float)($data['igv'] ?? 18.00);
            $priceWithIgv = (float)$data['price_with_igv'];
            $data['price_soles'] = $priceWithIgv / (1 + ($igv / 100));
            unset($data['price_with_igv']);
        }

        // Convertir precio original CON IGV a precio original SIN IGV
        if (isset($data['original_price_with_igv']) && $data['original_price_with_igv'] > 0) {
            $igv = (float)($data['igv'] ?? 18.00);
            $originalPriceWithIgv = (float)$data['original_price_with_igv'];
            $data['original_price_soles'] = $originalPriceWithIgv / (1 + ($igv / 100));
            unset($data['original_price_with_igv']);
        }

        return $data;
    }
}
