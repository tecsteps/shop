<?php

namespace App\Http\Requests\Storefront\Carts;

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
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
