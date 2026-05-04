<?php

namespace App\Http\Requests\Api\Storefront\V1;

use Illuminate\Foundation\Http\FormRequest;

class SuggestProductsRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
