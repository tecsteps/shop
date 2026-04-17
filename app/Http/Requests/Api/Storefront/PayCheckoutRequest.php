<?php

namespace App\Http\Requests\Api\Storefront;

use App\Enums\PaymentMethod;
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
        return [
            'payment_method' => ['required', 'string', 'in:'.implode(',', PaymentMethod::values())],
            'card_number' => ['nullable', 'string', 'max:32'],
        ];
    }
}
