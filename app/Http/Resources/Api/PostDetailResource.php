<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PostDetailResource extends JsonResource
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
            'title' => $this->title, // title_1
            'slug' => $this->slug,
            'desc_1' => $this->content, // desc_1
            'title_2' => $this->title_2,
            'desc_2' => $this->content_2,
            'featured_image' => $this->featured_image ? asset($this->featured_image) : null,
            'publish_at' => $this->publish_at?->format('Y-m-d H:i:s'),
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->name,
                    'avatar' => $this->author->avatar_url ? asset($this->author->avatar_url) : null,
                ];
            }),
            'tag' => $this->whenLoaded('tag', fn () => new TagResource($this->tag)),
            // 'seo_title' => $this->seo_title,
            // 'seo_description' => $this->seo_description,
            // 'seo_keywords' => $this->seo_keywords,
        ];
    }
}
