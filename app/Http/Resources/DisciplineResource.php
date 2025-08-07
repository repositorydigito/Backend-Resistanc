<?php

namespace App\Http\Resources;

use App\Filament\Resources\InstructorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisciplineResource extends JsonResource
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
            // 'display_name' => $this->display_name,
            // 'description' => $this->description,
            'icon_url' => $this->icon_url ? asset('storage/') . '/' . $this->icon_url : null,
            'color_hex' => $this->color_hex,
            // 'equipment_required' => $this->equipment_required,
            // 'difficulty_level' => $this->difficulty_level,
            // 'calories_per_hour_avg' => $this->calories_per_hour_avg,
            // 'is_active' => $this->is_active,
            // 'sort_order' => $this->sort_order,
            // 'created_at' => $this->created_at?->toISOString(),
            // 'updated_at' => $this->updated_at?->toISOString(),
            // 'classes_count' => $this->whenCounted('classes'),
            // 'instructors_count' => $this->whenCounted('instructors'),
            // 'classes' => ClassResource::collection($this->whenLoaded('classes')),
            // 'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),
        ];
    }
}
