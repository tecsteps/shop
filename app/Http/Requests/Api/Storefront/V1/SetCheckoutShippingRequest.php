<?php

namespace App\Http\Requests\Api\Storefront\V1;

use Illuminate\Foundation\Http\FormRequest;

class SetCheckoutShippingRequest extends FormRequest
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
            'shipping_rate_id' => ['nullable', 'integer', 'exists:shipping_rates,id'],
        ];
    }
}
