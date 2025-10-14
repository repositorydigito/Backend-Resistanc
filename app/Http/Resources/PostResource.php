<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'title' => $this->title,
            'image_path' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'content' => $this->content,
            'date_published' => $this->date_published ?
                Carbon::parse($this->date_published)->format('d \d\e F \d\e Y') :
                null,
            'created_at' => $this->created_at ?
                $this->created_at->translatedFormat('d \d\e F \d\e Y') :
                null,
            'user' =>
            [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'tags' => TagResource::collection($this->whenLoaded('tags')),

        ];
    }
}
