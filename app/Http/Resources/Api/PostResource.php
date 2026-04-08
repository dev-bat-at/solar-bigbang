<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'content' => $this->content,
            'slug' => $this->slug,
            'featured_image' => $this->featured_image ? asset($this->featured_image) : null,
            'publish_at' => $this->publish_at?->format('Y-m-d H:i:s'),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
