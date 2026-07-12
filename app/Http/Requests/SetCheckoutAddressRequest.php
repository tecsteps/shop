<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCheckoutAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'shipping_address' => ['required', 'array'], 'shipping_address.first_name' => ['required', 'string'], 'shipping_address.last_name' => ['required', 'string'], 'shipping_address.address1' => ['required', 'string'], 'shipping_address.city' => ['required', 'string'], 'shipping_address.country_code' => ['required', 'string', 'size:2'], 'shipping_address.postal_code' => ['required', 'string']];
    }
}
