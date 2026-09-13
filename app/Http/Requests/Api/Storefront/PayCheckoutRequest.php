<?php

namespace App\Http\Requests\Api\Storefront;

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
            'payment_method' => ['required', 'string', Rule::in(['credit_card', 'paypal', 'bank_transfer'])],
            'card_number' => 'required_if:payment_method,credit_card|nullable|string|max:32',
            'card_expiry' => 'required_if:payment_method,credit_card|nullable|string|max:7',
            'card_cvc' => 'required_if:payment_method,credit_card|nullable|string|max:4',
            'card_holder' => 'required_if:payment_method,credit_card|nullable|string|max:255',
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
            'card_number.required_if' => 'The card number field is required.',
            'card_expiry.required_if' => 'The card expiry field is required.',
            'card_cvc.required_if' => 'The card cvc field is required.',
            'card_holder.required_if' => 'The card holder field is required.',
        ];
    }
}
