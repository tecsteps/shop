<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SetAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:500'],
            'shipping_address.address2' => ['sometimes', 'nullable', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.province' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            'shipping_address.country_code' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'billing_address.first_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.last_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address1' => ['required_with:billing_address', 'string', 'max:500'],
            'billing_address.address2' => ['sometimes', 'nullable', 'string', 'max:500'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.province' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_address.province_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'use_shipping_as_billing' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'shipping_address.required' => 'A shipping address is required.',
            'shipping_address.first_name.required' => 'First name is required.',
            'shipping_address.last_name.required' => 'Last name is required.',
            'shipping_address.address1.required' => 'Street address is required.',
            'shipping_address.city.required' => 'City is required.',
            'shipping_address.country.required' => 'Country is required.',
            'shipping_address.country_code.required' => 'Country code is required.',
            'shipping_address.country_code.size' => 'Country code must be a 2-letter ISO code.',
            'shipping_address.postal_code.required' => 'Postal code is required.',
        ];
    }
}
