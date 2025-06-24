<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generar número de pedido automático si no se proporciona
        if (empty($data['order_number'])) {
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $lastNumber = $lastOrder ? (int) str_replace('ORD-', '', $lastOrder->order_number) : 0;
            $data['order_number'] = 'ORD-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }

        if (isset($data['order_items']) && is_array($data['order_items'])) {
            $subtotal = 0;
            foreach ($data['order_items'] as &$item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $item['unit_price_soles'] = $product->price_soles;
                    $item['total_price_soles'] = $product->price_soles * $item['quantity'];
                    $subtotal += $item['total_price_soles'];
                }
            }
            $data['subtotal_soles'] = $subtotal;

            $tax = $data['tax_amount_soles'] ?? 0;
            $shipping = $data['shipping_amount_soles'] ?? 0;
            $discount = $data['discount_amount_soles'] ?? 0;
            $data['total_amount_soles'] = $subtotal + $tax + $shipping - $discount;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
