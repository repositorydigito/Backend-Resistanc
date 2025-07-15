<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'full_sku' => $this->full_sku,
            'price_soles' => $this->price_soles,
            'cost_price_soles' => $this->cost_price_soles,
            'compare_price_soles' => $this->compare_price_soles,
            'final_price' => $this->final_price,
            'stock_quantity' => $this->stock_quantity,
            'min_stock_alert' => $this->min_stock_alert,
            'is_in_stock' => $this->is_in_stock,
            'is_active' => $this->is_active,
            'main_image' =>  url('storage/' . ltrim($this->main_image, '/')),
            'variant_options' => $this->variantOptions->map(function ($option) {
                return [
                    'id' => $option->id,
                    'type' => $option->productOptionType->name,
                    'name' => $option->name,
                    'is_color' => (bool) $option->productOptionType->is_color, // Asumiendo que tienes un campo 'type' en la opción
                    'value' => $option->value, // Asumiendo que tienes un pivot con el valor de la opción
                ];
            }),

            'images' => collect($this->images)->map(fn($path) => url('storage/' . ltrim($path, '/'))), // Asegúrate de que las imágenes estén en el formato correcto
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
