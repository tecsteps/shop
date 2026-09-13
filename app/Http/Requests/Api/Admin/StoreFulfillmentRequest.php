<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFulfillmentRequest extends FormRequest
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
            'tracking_company' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:2048',
            'line_items' => 'required|array|min:1',
            'line_items.*.order_line_id' => 'required|integer|min:1',
            'line_items.*.quantity' => 'required|integer|min:1',
            'notify_customer' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'line_items.required' => 'At least one line item is required.',
        ];
    }
}
