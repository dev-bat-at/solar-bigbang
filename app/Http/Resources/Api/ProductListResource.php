<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = is_array($this->images) ? $this->images : [];
        $primaryImage = count($images) > 0 ? asset($images[0]) : null;

        return [
           

            'vi' => [
                'id' => $this->id,
                'code' => $this->code,
                'slug' => $this->slug,
                'status' => $this->status,
                'is_best_seller' => $this->is_best_seller,
                // Giá trị trả về trực tiếp cho app xử lý hiển thị
                'price' => $this->price,
                'is_price_contact' => (bool) $this->is_price_contact,
                'power' => $this->power,
                // 'warranty_vi' => $this->warranty_vi, // Hỗ trợ cả 2 bản bảo hành nếu Front-end chọn dùng gốc
                // 'warranty_en' => $this->warranty_en,
                'primary_image' => $primaryImage,
                'name' => $this->name_vi,
                'warranty' => $this->warranty_vi,
            ],
            'en' => [
                'id' => $this->id,
                'code' => $this->code,
                'slug' => $this->slug,
                'status' => $this->status,
                'is_best_seller' => $this->is_best_seller,
                // Giá trị trả về trực tiếp cho app xử lý hiển thị
                'price' => $this->price,
                'is_price_contact' => (bool) $this->is_price_contact,
                'power' => $this->power,
                // 'warranty_vi' => $this->warranty_vi, // Hỗ trợ cả 2 bản bảo hành nếu Front-end chọn dùng gốc
                // 'warranty_en' => $this->warranty_en,
                'primary_image' => $primaryImage,
                'name' => $this->name_en,
                'warranty' => $this->warranty_en,
            ],
        ];
    }
}
