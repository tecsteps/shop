<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'The code field is required.',
        ];
    }
}
