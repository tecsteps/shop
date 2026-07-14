<?php

namespace App\Http\Requests;

use App\Models\Discount;

class UpdateDiscountRequest extends StoreDiscountRequest
{
    public function authorize(): bool
    {
        $discount = $this->route('discount');
        $discount = $discount instanceof Discount ? $discount : Discount::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->find($this->route('discountId'));

        return $discount !== null && ($this->user()?->can('update', $discount) ?? false);
    }

    public function rules(): array
    {
        return collect(parent::rules())->map(fn (array $rules): array => ['sometimes', ...array_values(array_filter($rules, fn (string $rule): bool => $rule !== 'required'))])->all();
    }
}
