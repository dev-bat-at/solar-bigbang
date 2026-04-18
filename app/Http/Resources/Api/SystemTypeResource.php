<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return static::toVietnameseArray($this->resource);
    }

    public static function toVietnameseArray(mixed $systemType): array
    {
        return [
            'id' => $systemType->id,
            'slug' => $systemType->slug,
            'name' => $systemType->name_vi ?: $systemType->name,
            'quote_enabled' => (bool) $systemType->quote_is_active,
            'quote_formula_type' => $systemType->quote_formula_type,
            'description' => $systemType->description_vi ?: $systemType->description,
        ];
    }

    public static function toEnglishArray(mixed $systemType): array
    {
        return [
            'id' => $systemType->id,
            'slug' => $systemType->slug,
            'name' => $systemType->name_en ?: $systemType->name_vi ?: $systemType->name,
            'quote_enabled' => (bool) $systemType->quote_is_active,
            'quote_formula_type' => $systemType->quote_formula_type,
            'description' => $systemType->description_en ?: $systemType->description_vi ?: $systemType->description,
        ];
    }
}
