<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Calcular subtotal si hay order items
        if (isset($data['order_items']) && is_array($data['order_items'])) {
            $subtotal = 0;
            foreach ($data['order_items'] as $item) {
                if (isset($item['total_price'])) {
                    $subtotal += (float) $item['total_price'];
                }
            }
            $data['subtotal_soles'] = $subtotal;
            
            // Calcular total final
            $tax = $data['tax_amount_soles'] ?? 0;
            $shipping = $data['shipping_amount_soles'] ?? 0;
            $discount = $data['discount_amount_soles'] ?? 0;
            $data['total_amount_soles'] = $subtotal + $tax + $shipping - $discount;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
