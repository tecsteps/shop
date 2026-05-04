<?php

namespace App\Http\Requests\Api\Admin\V1;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');
        $order = $this->route('order');

        $store = $store instanceof Store ? $store : Store::query()->find($store);

        if (! $store instanceof Store) {
            return false;
        }

        app()->instance('current_store', $store);

        if (! $order instanceof Order || (int) $order->store_id !== $store->getKey()) {
            return true;
        }

        return $this->user()?->can('createRefund', $order) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'restock' => ['sometimes', 'boolean'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.order_line_id' => ['required_with:line_items', 'integer'],
            'line_items.*.quantity' => ['required_with:line_items', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function lineQuantities(): array
    {
        return collect($this->validated('line_items', []))
            ->mapWithKeys(fn (array $line): array => [(int) $line['order_line_id'] => (int) $line['quantity']])
            ->all();
    }
}
