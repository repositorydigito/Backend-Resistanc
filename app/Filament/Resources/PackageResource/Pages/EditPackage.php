<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Filament\Resources\PackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
        } elseif (isset($data['original_price_with_igv']) && $data['original_price_with_igv'] == 0) {
            // Si se establece en 0, también establecer el precio original sin IGV en null
            $data['original_price_soles'] = null;
            unset($data['original_price_with_igv']);
        }

        return $data;
    }
}
