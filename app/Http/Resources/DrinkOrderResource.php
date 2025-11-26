<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrinkOrderResource extends JsonResource
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
            'quantity' => $this->quantity,
            'status' => $this->status,
            'total_price' => round($this->drink->price * $this->quantity, 2),

            // Información básica de la bebida
            'drink' => [
                'id' => $this->drink->id,
                'name' => $this->drink->name,
                'price' => (float) $this->drink->price,
                'image_url' => $this->drink->image_url  ? $this->drink->image_url : asset('default/protico.png'),
            ],

            // Clase asociada (si existe)
            'class_schedule_id' => $this->classschedule_id,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
