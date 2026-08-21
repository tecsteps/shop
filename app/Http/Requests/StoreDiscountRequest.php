<?php

namespace App\Http\Requests;

use App\Models\Discount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Discount::class);
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
            'code' => ['required_if:type,code', 'nullable', 'string', 'max:50'],
            'value_type' => ['required', 'in:fixed,percent,free_shipping'],
            'value_amount' => ['required_unless:value_type,free_shipping', 'integer', 'min:0'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'rules_json' => ['nullable', 'array'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->sometimes('value_amount', ['max:100'], fn (): bool => $this->input('value_type') === 'percent');
    }
}
