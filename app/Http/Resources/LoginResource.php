<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            'nombre' => $this->name,
            'correo' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'nombre' => $role->name,
                        'guard_name' => $role->guard_name,
                    ];
                });
            }),
            'profile' => $this->whenLoaded('profile', function () {
                return [
                    'id' => $this->profile->id,
                    'first_name' => $this->profile->first_name,
                    'last_name' => $this->profile->last_name,
                    'gender' => $this->profile->gender,
                    'birth_date' => $this->profile->birth_date,
                    'shoe_size_eu' => $this->profile->shoe_size_eu,
                    'profile_image' => $this->profile->profile_image,
                ];
            }),

            'token' => $this->token,
            // 'has_complete_profile' => $this->hasCompleteProfile(),


        ];
    }
}
