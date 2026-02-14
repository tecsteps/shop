<?php

namespace App\Http\Requests\Storefront\Carts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartLineRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'cart_version' => ['required_without:expected_version', 'integer', 'min:1'],
            'expected_version' => ['required_without:cart_version', 'integer', 'min:1'],
        ];
    }
}
