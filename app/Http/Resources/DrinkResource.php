<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrinkResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url ? asset('storage/'.$this->image_url) : null,
            'price' => $this->price,

            // ✅ Siempre cargar relaciones (sin whenLoaded)
            'bases' => $this->basesdrinks->map(function ($base) {
                return [
                    'id' => $base->id,
                    'name' => $base->name,
                    'description' => $base->description ?? null,
                ];
            }),

            'flavors' => $this->flavordrinks->map(function ($flavor) {
                return [
                    'id' => $flavor->id,
                    'name' => $flavor->name,
                    'description' => $flavor->description ?? null,
                ];
            }),

            'types' => $this->typesdrinks->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description ?? null,
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
