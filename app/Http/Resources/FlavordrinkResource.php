<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlavordrinkResource extends JsonResource
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
            'image_url' => $this->image_url ? asset('storage/'. $this->image_url) : asset('default/protico.png'),
            'ico_url' => $this->ico_url ?asset('storage/'. $this->ico_url) : asset('default/icon.png'),

        ];
    }
}
