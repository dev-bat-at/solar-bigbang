<?php

namespace App\Http\Resources\Api;

use App\Support\Media\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerAuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status,
            'avatar' => PublicAsset::url($this->avatar),
        ];
    }
}
