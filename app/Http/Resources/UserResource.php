<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para Usuario (vista básica)
 *
 * @property \App\Models\User $resource
 */
final class UserResource extends JsonResource
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
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed attributes
            'full_name' => $this->full_name,
            'has_complete_profile' => $this->hasCompleteProfile(),

            // Basic profile info (if loaded)
            'profile' => $this->whenLoaded('profile', function () {
                return new UserProfileResource($this->profile);
            }),

            // Primary contact (if loaded)
            'primary_contact' => $this->whenLoaded('primaryContact', function () {
                return new UserContactResource($this->primaryContact);
            }),
        ];
    }
}
