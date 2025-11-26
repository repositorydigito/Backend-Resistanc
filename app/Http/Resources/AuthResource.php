<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para respuestas de autenticación
 *
 * @property \App\Models\User $resource
 */
final class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
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
                    if ($this->profile) {
                        return new UserProfileResource($this->profile);
                    }
                    return null;
                }),


            ],

            // Token information (when provided)
            'token' => $this->when(
                isset($this->token),
                fn () => [
                    'access_token' => $this->token,
                    'token_type' => 'Bearer',
                    'expires_at' => $this->when(
                        isset($this->token_expires_at),
                        $this->token_expires_at
                    ),
                ]
            ),

            // Additional metadata
            'meta' => [
                'login_count' => $this->whenLoaded('loginAudits', function () {
                    return $this->loginAudits->count();
                }),
                'last_login' => $this->whenLoaded('loginAudits', function () {
                    return $this->loginAudits->sortByDesc('created_at')->first()?->created_at?->toISOString();
                }),
            ],
        ];
    }
}
