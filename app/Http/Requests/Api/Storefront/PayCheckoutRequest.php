<?php

namespace App\Http\Requests\Api\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class PayCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = [
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,bank_transfer'],
        ];

        if ($this->input('payment_method') === 'credit_card') {
            $rules['card_number'] = ['required', 'string'];
            $rules['card_expiry'] = ['required', 'string'];
            $rules['card_cvc'] = ['required', 'string', 'min:3', 'max:4'];
            $rules['card_holder'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }
}
