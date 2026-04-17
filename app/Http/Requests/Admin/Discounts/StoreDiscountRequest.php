<?php

namespace App\Http\Requests\Admin\Discounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['code', 'automatic'])],
            'code' => ['nullable', 'required_if:type,code', 'string', 'max:50'],
            'value_type' => ['required', 'string', Rule::in(['fixed', 'percent', 'free_shipping'])],
            'value_amount' => ['required', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_count' => ['nullable', 'integer', 'min:0'],
            'rules_json' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'active', 'expired', 'disabled'])],
        ];
    }
}
