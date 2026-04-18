<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDealerProjectRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('title') && $this->filled('name')) {
            $this->merge([
                'title' => $this->input('name'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'system_type_id' => ['required', 'integer', Rule::exists('system_types', 'id')],
            'province_id' => [
                'required',
                'integer',
                Rule::exists('provinces', 'id')->where(
                    fn ($query) => $query->whereNull('parent_id')->where('is_active', true)
                ),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:255'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'capacity' => ['required', 'string', 'max:255'],
            'completion_date' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên công trình',
            'title' => 'tên công trình',
            'system_type_id' => 'hệ',
            'province_id' => 'tỉnh thành',
            'price' => 'giá tiền',
            'address' => 'địa chỉ cụ thể',
            'images' => 'ảnh công trình',
            'images.*' => 'ảnh công trình',
            'description' => 'mô tả cấu hình',
            'capacity' => 'công suất',
            'completion_date' => 'thời gian hoàn thành',
        ];
    }
}
