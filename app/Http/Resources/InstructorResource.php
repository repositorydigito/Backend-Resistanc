<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'specialties' => $this->specialties,
            'bio' => $this->bio,
            'certifications' => $this->certifications,
            'profile_image' => $this->profile_image,
            'instagram_handle' => $this->instagram_handle,
            'is_head_coach' => $this->is_head_coach,
            'experience_years' => $this->experience_years,
            'rating_average' => $this->rating_average,
            'total_classes_taught' => $this->total_classes_taught,
            'hire_date' => $this->hire_date,
            'hourly_rate_soles' => $this->hourly_rate_soles,
            'status' => $this->status,
            'availability_schedule' => $this->availability_schedule,
            'type_document' => $this->type_document,
            'document_number' => $this->document_number,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

    }
}
