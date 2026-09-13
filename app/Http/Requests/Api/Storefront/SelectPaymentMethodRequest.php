<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectPaymentMethodRequest extends FormRequest
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
            'payment_method' => ['required', 'string', Rule::in(['credit_card', 'paypal', 'bank_transfer'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'The payment method field is required.',
            'payment_method.in' => 'The selected payment method is invalid.',
        ];
    }
}
