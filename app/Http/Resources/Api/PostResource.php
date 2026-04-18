<?php

namespace App\Http\Resources\Api;

use App\Support\Media\PublicAsset;
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
            'content' => $this->content,
            'slug' => $this->slug,
            'featured_image' => PublicAsset::url($this->featured_image),
            'publish_at' => $this->publish_at?->format('Y-m-d H:i:s'),
            'tag' => $this->whenLoaded('tag', fn () => new TagResource($this->tag)),
        ];
    }
}
