<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_vi' => $this->name_vi ?: $this->name,
            'name_en' => $this->name_en ?: $this->name_vi ?: $this->name,
            'description_vi' => $this->description_vi ?: $this->description,
            'description_en' => $this->description_en ?: $this->description_vi ?: $this->description,
            'quote_enabled' => (bool) $this->quote_is_active,
            'quote_formula_type' => $this->quote_formula_type,
            'show_calculation_formula' => (bool) $this->show_calculation_formula,
            'input_mode' => $this->quote_is_active ? $this->quote_input_mode : null,
            'quote_fields' => $this->quote_is_active ? $this->quote_form_fields : [],
            'formula_content_vi' => ($this->quote_is_active && $this->show_calculation_formula) ? $this->quote_formula_content_vi : null,
            'formula_content_en' => ($this->quote_is_active && $this->show_calculation_formula) ? $this->quote_formula_content_en : null,
        ];
    }
}
