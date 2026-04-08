<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'avatar' => $this->avatar ? asset($this->avatar) : null,
            'projects' => ProjectResource::collection($this->whenLoaded('projects')),
        ];
    }
}
