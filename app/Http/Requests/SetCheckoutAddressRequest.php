<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCheckoutAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string'],
            'shipping_address.last_name' => ['required', 'string'],
            'shipping_address.address1' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string'],
            'billing_address' => ['sometimes', 'array'],
        ];
    }
}
