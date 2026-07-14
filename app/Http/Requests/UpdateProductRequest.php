<?php

namespace App\Http\Requests;

use App\Models\Product;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');
        $product = $product instanceof Product ? $product : Product::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->find($this->route('productId'));

        return $product !== null && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        return collect(parent::rules())->map(fn (array $rules): array => ['sometimes', ...array_values(array_filter($rules, fn (string $rule): bool => $rule !== 'required'))])->all();
    }
}
