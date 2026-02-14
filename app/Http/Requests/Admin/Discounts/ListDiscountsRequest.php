<?php

namespace App\Http\Requests\Admin\Discounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDiscountsRequest extends FormRequest
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
            'type' => ['nullable', 'string', Rule::in(['code', 'automatic'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'expired', 'disabled'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
