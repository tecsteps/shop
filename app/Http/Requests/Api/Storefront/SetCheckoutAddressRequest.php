<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class SetCheckoutAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:500'],
            'shipping_address.address2' => ['nullable', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            'shipping_address.country_code' => ['required', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'array'],
            'use_shipping_as_billing' => ['nullable', 'boolean'],
        ];
    }
}
