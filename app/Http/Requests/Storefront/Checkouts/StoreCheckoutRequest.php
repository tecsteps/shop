<?php

namespace App\Http\Requests\Storefront\Checkouts;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutRequest extends FormRequest
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
            'cart_id' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
