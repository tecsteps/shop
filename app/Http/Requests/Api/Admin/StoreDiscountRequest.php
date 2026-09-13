<?php

namespace App\Http\Requests\Api\Admin;

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
        $storeId = (int) $this->route('storeId');

        return [
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'code' => [
                'required_if:type,code',
                'nullable',
                'string',
                'max:50',
                Rule::unique('discounts', 'code')->where('store_id', $storeId),
            ],
            'value_type' => ['required', Rule::in(['fixed', 'percent', 'free_shipping'])],
            'value_amount' => 'required|integer|min:0|max:100000000',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => ['nullable', Rule::in(['draft', 'active', 'disabled'])],
            'rules_json' => 'nullable|array',
            'rules_json.minimum_purchase_amount' => 'nullable|integer|min:0',
            'rules_json.min_purchase_amount' => 'nullable|integer|min:0',
            'rules_json.applicable_product_ids' => 'nullable|array',
            'rules_json.applicable_product_ids.*' => 'integer|min:1',
            'rules_json.applicable_collection_ids' => 'nullable|array',
            'rules_json.applicable_collection_ids.*' => 'integer|min:1',
            'rules_json.customer_eligibility' => ['nullable', Rule::in(['all', 'specific_customers', 'specific_segments'])],
            'rules_json.once_per_customer' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required_if' => 'The code field is required for code discounts.',
            'code.unique' => 'The code has already been taken.',
            'value_type.required' => 'The value type field is required.',
            'value_amount.required' => 'The value field is required.',
            'ends_at.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
