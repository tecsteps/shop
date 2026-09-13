<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
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
            'quantity_on_hand' => 'required_without:policy|nullable|integer|min:0',
            'policy' => ['required_without:quantity_on_hand', 'nullable', Rule::in(['deny', 'continue'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity_on_hand.required_without' => 'Either quantity_on_hand or policy must be provided.',
            'policy.required_without' => 'Either quantity_on_hand or policy must be provided.',
        ];
    }
}
