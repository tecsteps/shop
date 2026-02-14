<?php

namespace App\Http\Requests\Storefront\Checkouts;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCheckoutDiscountRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
        ];
    }
}
