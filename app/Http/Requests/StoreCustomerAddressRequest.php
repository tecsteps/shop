<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['label' => ['nullable', 'string', 'max:100'], 'address' => ['required', 'array'], 'address.first_name' => ['required', 'string'], 'address.last_name' => ['required', 'string'], 'address.address1' => ['required', 'string'], 'address.city' => ['required', 'string'], 'address.country_code' => ['required', 'string', 'size:2'], 'address.postal_code' => ['required', 'string'], 'is_default' => ['sometimes', 'boolean']];
    }
}
