<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class CreateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = Order::withoutGlobalScopes()->where('store_id', app('current_store')->id)
            ->find($this->route('orderId') ?? $this->route('order'));

        return $order !== null && ($this->user()?->can('createRefund', $order) ?? false);
    }

    public function rules(): array
    {
        return ['amount' => ['required_without_all:lines,line_items', 'nullable', 'integer', 'min:1'], 'lines' => ['sometimes', 'array'], 'lines.*' => ['integer', 'min:1'], 'line_items' => ['sometimes', 'array'], 'line_items.*.order_line_id' => ['required_with:line_items', 'integer'], 'line_items.*.quantity' => ['required_with:line_items', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:1000'], 'restock' => ['sometimes', 'boolean'], 'notify_customer' => ['sometimes', 'boolean']];
    }
}
