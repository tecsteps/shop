<?php

namespace App\Http\Requests\Admin;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases()))],
            'financial_status' => ['nullable', Rule::in(array_map(fn (FinancialStatus $status): string => $status->value, FinancialStatus::cases()))],
            'fulfillment_status' => ['nullable', Rule::in(array_map(fn (FulfillmentStatus $status): string => $status->value, FulfillmentStatus::cases()))],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists((new Customer)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'created_after' => ['nullable', 'date'],
            'created_before' => ['nullable', 'date'],
            'query' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['placed_at_desc', 'placed_at_asc', 'total_desc', 'total_asc'])],
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }
}
