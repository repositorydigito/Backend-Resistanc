<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'total_amount_soles' => $this->total_amount_soles,
            'currency' => $this->currency,
            'status' => $this->status,
            'order_date' => $this->created_at->toIso8601String(),
            'order_items_count' => $this->whenCounted('orderItems'),
            'items_preview' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
