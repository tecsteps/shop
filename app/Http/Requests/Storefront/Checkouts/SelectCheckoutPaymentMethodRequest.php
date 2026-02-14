<?php

namespace App\Http\Requests\Storefront\Checkouts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectCheckoutPaymentMethodRequest extends FormRequest
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
}
