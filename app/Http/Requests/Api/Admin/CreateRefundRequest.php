<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.order_line_id' => ['required_with:line_items', 'integer'],
            'line_items.*.quantity' => ['required_with:line_items', 'integer', 'min:1'],
            'notify_customer' => ['nullable', 'boolean'],
        ];
    }
}
