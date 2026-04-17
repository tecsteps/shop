<?php

namespace App\Http\Requests\Admin\Discounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
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
            'type' => ['sometimes', 'string', Rule::in(['code', 'automatic'])],
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'value_type' => ['sometimes', 'string', Rule::in(['fixed', 'percent', 'free_shipping'])],
            'value_amount' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'usage_count' => ['sometimes', 'integer', 'min:0'],
            'rules_json' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'expired', 'disabled'])],
        ];
    }
}
