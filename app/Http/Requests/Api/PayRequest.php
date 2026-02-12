<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'nullable', 'string'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'regex:/^\d{2}\/\d{2}$/'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'regex:/^\d{3,4}$/'],
            'card_holder' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'A payment method is required.',
            'payment_method.in' => 'Payment method must be credit_card, paypal, or bank_transfer.',
            'card_number.required_if' => 'Card number is required for credit card payments.',
            'card_expiry.required_if' => 'Card expiry is required for credit card payments.',
            'card_expiry.regex' => 'Card expiry must be in MM/YY format.',
            'card_cvc.required_if' => 'Card CVC is required for credit card payments.',
            'card_cvc.regex' => 'Card CVC must be 3-4 digits.',
            'card_holder.required_if' => 'Card holder name is required for credit card payments.',
        ];
    }
}
