<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cart_id' => ['required', 'integer', 'exists:carts,id'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cart_id.required' => 'A cart is required to create a checkout.',
            'cart_id.exists' => 'The specified cart does not exist.',
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }
}
