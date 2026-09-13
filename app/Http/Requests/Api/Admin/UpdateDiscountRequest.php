<?php

namespace App\Http\Requests\Api\Admin;

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
            'value_type' => ['nullable', Rule::in(['fixed', 'percent', 'free_shipping'])],
            'value_amount' => 'nullable|integer|min:0|max:100000000',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'status' => ['nullable', Rule::in(['draft', 'active', 'disabled', 'expired'])],
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
}
