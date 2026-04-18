<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $images = is_array($this->images) 
            ? collect($this->images)->map(fn($img) => asset($img))->toArray() 
            : [];

        $category = new ProductCategoryResource($this->whenLoaded('productCategory'));
        $subcategory = new ProductCategoryResource($this->whenLoaded('productSubcategory'));

        $specifications_vi = collect($this->specifications ?? [])->map(fn($item) => ['label' => $item['label_vi'] ?? '', 'value' => $item['value_vi'] ?? ''])->toArray();
        $specifications_en = collect($this->specifications ?? [])->map(fn($item) => ['label' => $item['label_en'] ?? '', 'value' => $item['value_en'] ?? ''])->toArray();
        
        $documents_vi = collect($this->documents ?? [])->map(fn($item) => ['name' => $item['name_vi'] ?? '', 'path' => isset($item['path']) && $item['path'] ? asset($item['path']) : ''])->toArray();
        $documents_en = collect($this->documents ?? [])->map(fn($item) => ['name' => $item['name_en'] ?? '', 'path' => isset($item['path']) && $item['path'] ? asset($item['path']) : ''])->toArray();
        
        $faqs_vi = collect($this->faqs ?? [])->map(fn($item) => ['question' => $item['question_vi'] ?? '', 'answer' => $item['answer_vi'] ?? ''])->toArray();
        $faqs_en = collect($this->faqs ?? [])->map(fn($item) => ['question' => $item['question_en'] ?? '', 'answer' => $item['answer_en'] ?? ''])->toArray();

        return [
            'vi' => [
                'id' => $this->id,
                'name' => $this->name_vi,
                'description' => $this->description_vi,
				'tagline' => $this->tagline_vi,
                'code' => $this->code,
                'slug' => $this->slug,
                'status' => $this->status,
                'is_best_seller' => $this->is_best_seller,
                'price' => $this->price,
                'is_price_contact' => (bool)$this->is_price_contact,
                'power' => $this->power,
                'efficiency' => $this->efficiency,
                'warranty' => $this->warranty_vi, // Mặc định hiển thị tiếng Việt lớp ngoài
                'images' => $images,
                'price_unit' => $this->price_unit_vi,
                'specifications' => $specifications_vi,
                'documents' => $documents_vi,
                'faqs' => $faqs_vi,
                'category' => $category,
                'subcategory' => $subcategory,
            ],
            'en' => [
                'id' => $this->id,
                'name' => $this->name_en,
				'description' => $this->description_en,
				'tagline' => $this->tagline_en,
                'code' => $this->code,
                'slug' => $this->slug,
                'status' => $this->status,
                'is_best_seller' => $this->is_best_seller,
                'price' => $this->price,
                'is_price_contact' => (bool)$this->is_price_contact,
                'power' => $this->power,
                'efficiency' => $this->efficiency,
                'warranty' => $this->warranty_vi, // Mặc định hiển thị tiếng Việt lớp ngoài
                'images' => $images,
                'price_unit' => $this->price_unit_en,
                'specifications' => $specifications_en,
                'documents' => $documents_en,
                'faqs' => $faqs_en,
                'category' => $category,
                'subcategory' => $subcategory,
            ],
        ];
    }
}
