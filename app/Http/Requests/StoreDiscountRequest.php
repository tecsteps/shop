<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['type' => ['required', 'in:code,automatic'], 'code' => ['nullable', 'string', 'max:100'], 'value_type' => ['required', 'in:fixed,percent,free_shipping'], 'value_amount' => ['required', 'integer', 'min:0'], 'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'rules_json' => ['sometimes', 'array'], 'status' => ['sometimes', 'in:draft,active,expired,disabled']];
    }
}
