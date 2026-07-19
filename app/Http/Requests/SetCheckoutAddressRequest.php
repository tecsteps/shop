<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for PUT /checkouts/{id}/address (spec 02 §2.2, spec 06 §4.4).
 */
class SetCheckoutAddressRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['sometimes', 'email', 'max:255'],
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.address1' => ['required', 'string', 'max:500'],
            'shipping_address.address2' => ['nullable', 'string', 'max:500'],
            'shipping_address.company' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            'shipping_address.country_code' => ['required', 'string', 'size:2', 'alpha'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.first_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.last_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address1' => ['required_with:billing_address', 'string', 'max:500'],
            'billing_address.address2' => ['nullable', 'string', 'max:500'],
            'billing_address.company' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.province' => ['nullable', 'string', 'max:255'],
            'billing_address.province_code' => ['nullable', 'string', 'max:10'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2', 'alpha'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
            'use_shipping_as_billing' => ['sometimes', 'boolean'],
        ];
    }
}
