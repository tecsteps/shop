<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateRefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = Order::withoutGlobalScopes()
            ->where('store_id', app('current_store')->getKey())
            ->find($this->route('orderId'));

        return $order instanceof Order && Gate::allows('createRefund', $order);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_id' => ['nullable', 'integer'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'lines' => ['nullable', 'array'],
            'lines.*.order_line_id' => ['required_with:lines', 'integer'],
            'lines.*.quantity' => ['required_with:lines', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
            'restock' => ['nullable', 'boolean'],
        ];
    }
}
