<?php

namespace App\Http\Requests;

use App\Models\Discount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class UpdateDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Discount::class);
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
            'code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'value_type' => ['sometimes', 'in:fixed,percent,free_shipping'],
            'value_amount' => ['sometimes', 'integer', 'min:0'],
            'minimum_order_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'rules_json' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->sometimes('value_amount', ['max:100'], fn (): bool => $this->input('value_type') === 'percent');
    }
}
