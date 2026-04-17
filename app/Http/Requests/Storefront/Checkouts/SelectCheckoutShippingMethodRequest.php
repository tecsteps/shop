<?php

namespace App\Http\Requests\Storefront\Checkouts;

use Illuminate\Foundation\Http\FormRequest;

class SelectCheckoutShippingMethodRequest extends FormRequest
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
            'shipping_method_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
