<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'title_vi' => $this->title_vi,
            'title_en' => $this->title_en,
            'content_vi' => $this->content_vi,
            'content_en' => $this->content_en,
            'payload' => $this->payload ?? (object) [],
            'read_at' => $this->read_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
