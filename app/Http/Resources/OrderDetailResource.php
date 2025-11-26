<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'order_date' => $this->created_at->toIso8601String(),
            'currency' => $this->currency,
            'subtotal_soles' => $this->subtotal_soles,
            'shipping_amount_soles' => $this->shipping_amount_soles,
            'discount_amount_soles' => $this->discount_amount_soles,
            'tax_amount_soles' => $this->tax_amount_soles,
            'total_amount_soles' => $this->total_amount_soles,
            'delivery_method' => $this->delivery_method,
            'delivery_date' => $this->delivery_date,
            'delivery_time_slot' => $this->delivery_time_slot,
            'delivery_address' => $this->delivery_address,
            'special_instructions' => $this->special_instructions,
            'notes' => $this->notes,
            'customer' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
