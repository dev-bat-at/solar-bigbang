<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'color' => $this->api_color,
            'vi' => [
                'id' => $this->id,
                'name' => $this->name_vi ?: $this->name,
                'slug' => $this->slug,
            ],
            'en' => [
                'id' => $this->id,
                'name' => $this->name_en ?: $this->name_vi ?: $this->name,
                'slug' => $this->slug,
            ],
        ];
    }
}
