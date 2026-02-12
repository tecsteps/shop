<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'currency.size' => 'Currency must be a valid 3-letter ISO 4217 code.',
        ];
    }
}
