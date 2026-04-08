<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'phone' => $this['phone'] ?? null,
            'zalo_link' => $this['zalo_link'] ?? null,
            'email' => $this['email'] ?? null,
            'business_hours' => $this['business_hours'] ?? null,
        ];
    }
}
