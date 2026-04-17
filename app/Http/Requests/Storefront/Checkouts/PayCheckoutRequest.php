<?php

namespace App\Http\Requests\Storefront\Checkouts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayCheckoutRequest extends FormRequest
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
            'payment_method' => ['nullable', 'string', Rule::in(['credit_card', 'paypal', 'bank_transfer'])],
            'card_number' => ['nullable', 'string', 'max:32'],
            'card_expiry' => ['nullable', 'string', 'max:5'],
            'card_cvc' => ['nullable', 'string', 'max:4'],
            'card_holder' => ['nullable', 'string', 'max:255'],
        ];
    }
}
