<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(6)],
            'province_id' => [
                'required',
                'integer',
                Rule::exists('provinces', 'id')->where(
                    fn ($query) => $query
                        ->whereNull('parent_id')
                        ->where('is_active', true)
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Số điện thoại này đã được đăng ký.',
            'email.unique' => 'Email này đã được đăng ký.',
        ];
    }
}
