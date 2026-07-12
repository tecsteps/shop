<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Support\SafeUrl;
use Illuminate\Foundation\Http\FormRequest;

class CreateFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = Order::withoutGlobalScopes()->where('store_id', app('current_store')->id)
            ->find($this->route('orderId') ?? $this->route('order'));

        return $order !== null && ($this->user()?->can('createFulfillment', $order) ?? false);
    }

    public function rules(): array
    {
        return ['line_items' => ['required_without:lines', 'array', 'min:1'], 'line_items.*.order_line_id' => ['required_with:line_items', 'integer'], 'line_items.*.quantity' => ['required_with:line_items', 'integer', 'min:1'], 'lines' => ['required_without:line_items', 'array', 'min:1'], 'lines.*' => ['required_with:lines', 'integer', 'min:1'], 'tracking_company' => ['nullable', 'string', 'max:100'], 'tracking_number' => ['nullable', 'string', 'max:255'], 'tracking_url' => [
            'nullable', 'string', 'max:2048',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! SafeUrl::isAllowed($value)) {
                    $fail('The tracking link must be a relative, HTTP, or HTTPS URL.');
                }
            },
        ], 'notify_customer' => ['sometimes', 'boolean']];
    }
}
