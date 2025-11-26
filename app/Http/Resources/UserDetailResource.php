<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para Usuario (vista detallada)
 *
 * Incluye perfil, contactos, cuentas sociales y auditoría de logins
 *
 * @property \App\Models\User $resource
 */
final class UserDetailResource extends JsonResource
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

            // Complete profile
            'profile' => $this->whenLoaded('profile', function () {
                return new UserProfileResource($this->profile);
            }),


        ];
    }
}
