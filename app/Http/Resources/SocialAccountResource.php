<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SocialAccountResource extends JsonResource
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
            'user_id' => $this->user_id,
            'provider' => $this->provider?->value,
            'provider_uid' => $this->provider_uid,
            'provider_email' => $this->provider_email,
            
            // Security: Never expose the actual token
            'has_token' => !empty($this->token),
            'token_expires_at' => $this->token_expires_at?->toISOString(),
            'is_token_expired' => $this->isTokenExpired(),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Related user (if loaded and requested)
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
        ];
    }
}
