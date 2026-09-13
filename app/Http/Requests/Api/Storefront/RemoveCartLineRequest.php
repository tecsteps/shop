<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCartLineRequest extends FormRequest
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
            'cart_version' => 'required|integer|min:1',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cart_version.required' => 'The cart version field is required.',
        ];
    }
}
