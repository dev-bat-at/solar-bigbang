<?php

namespace App\Http\Resources\Api;

use App\Models\Project;
use App\Support\Media\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->title,
            'system_type' => $this->whenLoaded('systemType', fn () => [
                'id' => $this->systemType?->id,
                'name' => $this->systemType?->name_vi ?: $this->systemType?->name,
                'name_vi' => $this->systemType?->name_vi ?: $this->systemType?->name,
                'name_en' => $this->systemType?->name_en ?: $this->systemType?->name_vi ?: $this->systemType?->name,
            ]),
            'capacity' => $this->capacity,
            'price' => $this->price !== null ? (float) $this->price : null,
            'province' => $this->whenLoaded('province', fn () => [
                'id' => $this->province?->id,
                'name' => $this->province?->name,
                'code' => $this->province?->code,
                'type' => $this->province?->type,
            ]),
            'address' => $this->address,
            'description' => $this->description,
            'images' => collect($this->images ?? [])
                ->map(fn ($path) => PublicAsset::url($path))
                ->filter()
                ->values()
                ->all(),
            'status' => $this->status,
            'status_label' => match ($this->status) {
                Project::STATUS_PENDING => 'Chờ duyệt',
                Project::STATUS_APPROVED => 'Đã duyệt',
                Project::STATUS_REJECTED => 'Bị từ chối',
                default => $this->status,
            },
            'rejection_reason' => $this->rejection_reason,
            'completion_date' => $this->completion_date?->format('Y-m-d'),
            'posted_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
