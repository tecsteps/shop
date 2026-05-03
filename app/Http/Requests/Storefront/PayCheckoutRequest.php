<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class PayCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('card_number')) {
            $this->merge([
                'card_number' => preg_replace('/\D+/', '', (string) $this->input('card_number')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'card_number' => ['required_if:payment_method,credit_card', 'nullable', 'digits:16'],
            'card_expiry' => ['required_if:payment_method,credit_card', 'nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card_cvc' => ['required_if:payment_method,credit_card', 'nullable', 'digits_between:3,4'],
            'card_holder' => ['required_if:payment_method,credit_card', 'nullable', 'string', 'max:255'],
        ];
    }
}
