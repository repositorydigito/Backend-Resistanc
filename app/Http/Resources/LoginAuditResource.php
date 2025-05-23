<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LoginAuditResource extends JsonResource
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
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'success' => $this->success,
            'created_at' => $this->created_at?->toISOString(),
            
            // Computed attributes
            'browser' => $this->browser,
            
            // Human readable
            'status' => $this->success ? 'successful' : 'failed',
            'time_ago' => $this->created_at?->diffForHumans(),
            
            // Related user (if loaded and requested)
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
        ];
    }
}
