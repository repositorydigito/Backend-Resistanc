<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class InstructorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if the authenticated user has rated this instructor
        $hasRated = false;
        if (Auth::check()) {
            $hasRated = $this->ratings()->where('user_id', Auth::id())->exists();
            $userRating = $this->ratings()->where('user_id', Auth::id())->first();
            $hasRated = $userRating !== null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_image' => $this->profile_image ? asset('storage/') . '/' . $this->profile_image : asset('default/entrenador.jpg'),
            'specialties' => $this->specialties,
            'bio' => $this->bio,
            'certifications' => $this->certifications,
            'instagram_handle' => $this->instagram_handle,
            'is_head_coach' => $this->is_head_coach,
            'experience_years' => $this->experience_years,
            'rating_average' => round($this->rating_average, 1),
            'total_classes_taught' => $this->total_classes_taught,
            'hire_date' => $this->hire_date,
            'hourly_rate_soles' => $this->hourly_rate_soles,
            'status' => $this->status,
            'has_rated' => $hasRated,
            'user_rating' => $userRating ?? null, // Include the user's rating score
            'disciplines' => $this->disciplines->map(function ($discipline) {
                return [
                    'id' => $discipline->id,
                    'name' => $discipline->name,
                    'icon_url' => $discipline->icon_url ? asset('storage/') . '/' . $discipline->icon_url : asset('default/icon.png'),
                    'color_hex' => $discipline->color_hex,
                ];
            }),
            'ratings_summary' => [
                'count' => $this->ratings->count(),
                'average' => round($this->ratings->avg('score'), 1),
                'distribution' => [
                    '5' => $this->ratings->where('score', 5)->count(),
                    '4' => $this->ratings->where('score', 4)->count(),
                    '3' => $this->ratings->where('score', 3)->count(),
                    '2' => $this->ratings->where('score', 2)->count(),
                    '1' => $this->ratings->where('score', 1)->count(),
                ],
            ],
            'type_document' => $this->type_document,
            'document_number' => $this->document_number,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
