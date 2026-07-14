<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetCheckoutShippingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['shipping_rate_id' => ['nullable', 'integer', 'exists:shipping_rates,id']];
    }
}
