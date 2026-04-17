<?php

namespace App\Http\Requests\Storefront\Checkouts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckoutAddressRequest extends FormRequest
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
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.first_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.last_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.address1' => ['required_with:shipping_address', 'string', 'max:500'],
            'shipping_address.address2' => ['nullable', 'string', 'max:500'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
            'shipping_address.country' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.country_code' => ['required_with:shipping_address', 'string', 'size:2'],
            'shipping_address.postal_code' => ['required_with:shipping_address', 'string', 'max:20'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],

            'billing_address' => ['nullable', 'array'],
            'billing_address.first_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.last_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address1' => ['required_with:billing_address', 'string', 'max:500'],
            'billing_address.address2' => ['nullable', 'string', 'max:500'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.province' => ['nullable', 'string', 'max:255'],
            'billing_address.province_code' => ['nullable', 'string', 'max:10'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],

            'use_shipping_as_billing' => ['nullable', 'boolean'],
        ];
    }
}
