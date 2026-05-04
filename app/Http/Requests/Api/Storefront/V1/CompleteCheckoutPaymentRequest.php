<?php

namespace App\Http\Requests\Api\Storefront\V1;

use Illuminate\Foundation\Http\FormRequest;

class CompleteCheckoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'string'],
            'card_holder' => ['required_if:payment_method,credit_card', 'string', 'max:255'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'string', 'max:20'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'string', 'max:10'],
        ];
    }
}
