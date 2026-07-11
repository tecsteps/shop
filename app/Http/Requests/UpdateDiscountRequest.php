<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
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
            'type' => ['sometimes', 'in:code,automatic'],
            'code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'value_type' => ['sometimes', 'in:percent,fixed,free_shipping'],
            'value_amount' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'rules_json' => ['sometimes', 'array'],
            'status' => ['sometimes', 'in:draft,active,expired,disabled'],
        ];
    }
}
