<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectPaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(['credit_card', 'paypal', 'bank_transfer'])],
        ];
    }
}
