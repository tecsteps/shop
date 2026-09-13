<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class AddCartLineRequest extends FormRequest
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
            'variant_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1|max:9999',
            'cart_version' => 'nullable|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'variant_id.required' => 'The variant field is required.',
            'quantity.required' => 'The quantity field is required.',
            'quantity.min' => 'The quantity must be at least 1.',
        ];
    }
}
