<?php

namespace App\Http\Requests\Admin;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::unique((new Product)->getTable(), 'handle')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
            'description_html' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([ProductStatus::Draft->value, ProductStatus::Active->value])],
            'tags' => ['nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['nullable', 'array', 'max:3'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.position' => ['nullable', 'integer', 'min:1', 'max:3'],
            'options.*.values' => ['nullable', 'array', 'max:100'],
            'options.*.values.*' => ['string', 'max:255'],
            'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['nullable', 'string', 'size:3'],
            'variants.*.weight_g' => ['nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['nullable', 'boolean'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.position' => ['nullable', 'integer', 'min:0'],
            'variants.*.status' => ['nullable', Rule::in(array_map(fn (VariantStatus $status): string => $status->value, VariantStatus::cases()))],
            'variants.*.inventory' => ['nullable', 'array'],
            'variants.*.inventory.quantity_on_hand' => ['nullable', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['nullable', Rule::in(array_map(fn (InventoryPolicy $policy): string => $policy->value, InventoryPolicy::cases()))],
            'collections' => ['nullable', 'array'],
            'collections.*' => [
                'integer',
                Rule::exists((new ProductCollection)->getTable(), 'id')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId())),
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateVariantDefaults($validator);
                $this->validateVariantSkus($validator);
            },
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }

    private function validateVariantDefaults(Validator $validator): void
    {
        $defaultCount = collect($this->input('variants', []))
            ->filter(fn (mixed $variant): bool => (bool) data_get($variant, 'is_default', false))
            ->count();

        if ($defaultCount > 1) {
            $validator->errors()->add('variants', 'Only one variant can be marked as default.');
        }
    }

    private function validateVariantSkus(Validator $validator): void
    {
        $skus = collect($this->input('variants', []))
            ->pluck('sku')
            ->filter()
            ->map(fn (mixed $sku): string => (string) $sku);

        if ($skus->duplicates()->isNotEmpty()) {
            $validator->errors()->add('variants', 'Variant SKUs must be unique within the request.');

            return;
        }

        if (ProductVariant::query()
            ->whereIn('sku', $skus->all())
            ->whereHas('product', fn ($query) => $query->where('store_id', $this->storeId()))
            ->exists()) {
            $validator->errors()->add('variants', 'Variant SKUs must be unique within the store.');
        }
    }
}
