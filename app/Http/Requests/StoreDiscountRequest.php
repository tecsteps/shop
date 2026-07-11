<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:code,automatic'],
            'code' => ['nullable', 'required_if:type,code', 'string', 'max:255'],
            'value_type' => ['required', 'in:percent,fixed,free_shipping'],
            'value_amount' => ['required', 'integer', 'min:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'rules_json' => ['sometimes', 'array'],
            'status' => ['sometimes', 'in:draft,active,expired,disabled'],
        ];
    }
}
