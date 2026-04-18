<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'contact_time' => $this->contact_time,
            'system_type' => $this->whenLoaded('systemType', fn () => [
                'id' => $this->systemType?->id,
                'name_vi' => $this->systemType?->name_vi ?: $this->systemType?->name,
                'name_en' => $this->systemType?->name_en ?: $this->systemType?->name_vi ?: $this->systemType?->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
