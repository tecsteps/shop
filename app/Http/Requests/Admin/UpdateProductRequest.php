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

class UpdateProductRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'handle' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::unique((new Product)->getTable(), 'handle')
                    ->where(fn ($query) => $query->where('store_id', $this->storeId()))
                    ->ignore($this->productId()),
            ],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(array_map(fn (ProductStatus $status): string => $status->value, ProductStatus::cases()))],
            'tags' => ['sometimes', 'nullable', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'variants' => ['sometimes', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['nullable', 'integer', 'min:0'],
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
            'collections' => ['sometimes', 'nullable', 'array'],
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
                $this->validateVariantOwnershipAndSkus($validator);
            },
        ];
    }

    private function storeId(): int
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store->id : (int) $store;
    }

    private function productId(): int
    {
        return (int) $this->route('product');
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

    private function validateVariantOwnershipAndSkus(Validator $validator): void
    {
        $variants = collect($this->input('variants', []));
        $variantIds = $variants->pluck('id')->filter()->map(fn (mixed $id): int => (int) $id)->values();

        if ($variantIds->isNotEmpty()) {
            $ownedCount = ProductVariant::query()
                ->whereIn('id', $variantIds->all())
                ->where('product_id', $this->productId())
                ->count();

            if ($ownedCount !== $variantIds->count()) {
                $validator->errors()->add('variants', 'Every variant id must belong to this product.');

                return;
            }
        }

        $skus = $variants
            ->pluck('sku')
            ->filter()
            ->map(fn (mixed $sku): string => (string) $sku);

        if ($skus->duplicates()->isNotEmpty()) {
            $validator->errors()->add('variants', 'Variant SKUs must be unique within the request.');

            return;
        }

        foreach ($variants as $variant) {
            $sku = data_get($variant, 'sku');

            if (! $sku) {
                continue;
            }

            $duplicateExists = ProductVariant::query()
                ->where('sku', (string) $sku)
                ->when(data_get($variant, 'id'), fn ($query, mixed $id) => $query->whereKeyNot((int) $id))
                ->whereHas('product', fn ($query) => $query->where('store_id', $this->storeId()))
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('variants', 'Variant SKUs must be unique within the store.');

                return;
            }
        }
    }
}
