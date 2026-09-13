<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutRequest extends FormRequest
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
            'cart_id' => 'required|integer|min:1',
            'email' => 'required|email|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cart_id.required' => 'The cart field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
        ];
    }
}
