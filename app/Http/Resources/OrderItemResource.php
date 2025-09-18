<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            'quantity' => $this->quantity,
            'unit_price_soles' => $this->unit_price_soles,
            'total_price_soles' => $this->total_price_soles,
            'notes' => $this->notes,
        ];
    }
}
