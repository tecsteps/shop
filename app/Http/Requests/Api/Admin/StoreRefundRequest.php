<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
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
            'amount' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
            'line_items' => 'nullable|array',
            'line_items.*.order_line_id' => 'required|integer|min:1',
            'line_items.*.quantity' => 'required|integer|min:1',
            'restock' => 'nullable|boolean',
            'notify_customer' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The amount field is required.',
            'amount.min' => 'The amount must be at least 1.',
        ];
    }
}
