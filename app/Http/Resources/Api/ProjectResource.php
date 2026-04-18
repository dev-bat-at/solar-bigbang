<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $images = is_array($this->images) 
            ? collect($this->images)->map(fn($v) => asset($v))->toArray() 
            : [];

        // Nếu có completion_date tức là đã thi công xong
        $isCompleted = ! empty($this->completion_date);

        return [
            'id' => $this->id,
            'name' => $this->title ?? current(explode(' ', $this->description)) . ' Project',
            'system_type' => $this->whenLoaded('systemType', fn () => [
                'name_vi' => $this->systemType->name_vi ?: $this->systemType->name,
                'name_en' => $this->systemType->name_en ?: $this->systemType->name_vi ?: $this->systemType->name,
            ]),
            'address' => $this->address,
            'images' => $images,
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? \Carbon\Carbon::parse($this->completion_date)->format('Y-m-d') : null,
        ];
    }
}
