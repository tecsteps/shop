<?php

namespace App\Http\Requests\Admin;

use App\Models\OrderLine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $orderId = (int) $this->route('order');
                $lineIds = collect($this->input('line_items', []))
                    ->pluck('order_line_id')
                    ->filter()
                    ->map(fn (mixed $id): int => (int) $id)
                    ->values();

                if ($lineIds->isEmpty()) {
                    return;
                }

                $ownedCount = OrderLine::query()
                    ->where('order_id', $orderId)
                    ->whereIn('id', $lineIds->all())
                    ->count();

                if ($ownedCount !== $lineIds->count()) {
                    $validator->errors()->add('line_items', 'Every refund line must belong to this order.');
                }
            },
        ];
    }
}
