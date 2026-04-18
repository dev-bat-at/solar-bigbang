<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'name' => $this->name_vi ?: $this->name,
            'vi' => [
                'id' => $this->id,
                'slug' => $this->slug,
                'sort_order' => $this->sort_order,
                'name' => $this->name_vi ?: $this->name,
                'children' => ProductCategoryResource::collection($this->whenLoaded('children')),

            ],
            'en' => [
                'id' => $this->id,
                'slug' => $this->slug,
                'sort_order' => $this->sort_order,
                'name' => $this->name_en ?: $this->name_vi ?: $this->name,
                'children' => ProductCategoryResource::collection($this->whenLoaded('children')),

            ],
        ];
    }
}
