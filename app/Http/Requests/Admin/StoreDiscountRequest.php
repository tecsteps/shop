<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiscountRequest extends FormRequest
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
            'type' => ['required', Rule::in(array_map(fn (DiscountType $type): string => $type->value, DiscountType::cases()))],
            'code' => [
                'required_if:type,'.DiscountType::Code->value,
                'nullable',
                'string',
                'max:50',
                Rule::unique((new Discount)->getTable(), 'code')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'value_type' => ['required', Rule::in(array_map(fn (DiscountValueType $type): string => $type->value, DiscountValueType::cases()))],
            'value_amount' => ['required', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'rules_json' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(array_map(fn (DiscountStatus $status): string => $status->value, DiscountStatus::cases()))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('code')) {
            $this->merge(['code' => strtoupper((string) $this->input('code'))]);
        }
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }
}
