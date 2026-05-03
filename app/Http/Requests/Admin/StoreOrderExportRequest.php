<?php

namespace App\Http\Requests\Admin;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Export;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderExportRequest extends FormRequest
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
            'format' => ['nullable', Rule::in([Export::FormatCsv])],
            'filters' => ['nullable', 'array'],
            'filters.status' => ['nullable', Rule::in(array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases()))],
            'filters.financial_status' => ['nullable', Rule::in(array_map(fn (FinancialStatus $status): string => $status->value, FinancialStatus::cases()))],
            'filters.fulfillment_status' => ['nullable', Rule::in(array_map(fn (FulfillmentStatus $status): string => $status->value, FulfillmentStatus::cases()))],
            'filters.customer_id' => [
                'nullable',
                'integer',
                Rule::exists((new Customer)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'filters.created_after' => ['nullable', 'date'],
            'filters.created_before' => ['nullable', 'date'],
            'filters.query' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }
}
