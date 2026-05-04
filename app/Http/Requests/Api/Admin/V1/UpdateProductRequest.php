<?php

namespace App\Http\Requests\Api\Admin\V1;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $store = $this->routeStore();
        $product = $this->routeProduct();

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'handle' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'handle')
                    ->where('store_id', $store->getKey())
                    ->ignore($product?->getKey()),
            ],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'tags' => ['sometimes', 'array', 'max:50'],
            'tags.*' => ['string', 'max:255'],
            'options' => ['sometimes', 'array', 'max:3'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.position' => ['required_with:options', 'integer', 'min:1', 'max:3'],
            'options.*.values' => ['sometimes', 'array', 'max:100'],
            'options.*.values.*' => ['string', 'max:255'],
            'variants' => ['sometimes', 'array', 'min:1', 'max:100'],
            'variants.*.id' => ['sometimes', 'integer'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price_amount' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['sometimes', 'string', 'size:3'],
            'variants.*.weight_g' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'variants.*.requires_shipping' => ['sometimes', 'boolean'],
            'variants.*.is_default' => ['required_with:variants', 'boolean'],
            'variants.*.position' => ['sometimes', 'integer', 'min:0'],
            'variants.*.status' => ['sometimes', Rule::in(['active', 'archived'])],
            'variants.*.option_values' => ['sometimes', 'array'],
            'variants.*.option_values.*.option_name' => ['required_with:variants.*.option_values', 'string', 'max:255'],
            'variants.*.option_values.*.value' => ['required_with:variants.*.option_values', 'string', 'max:255'],
            'variants.*.inventory' => ['sometimes', 'array'],
            'variants.*.inventory.quantity_on_hand' => ['sometimes', 'integer', 'min:0'],
            'variants.*.inventory.policy' => ['sometimes', Rule::in(['deny', 'continue'])],
            'collections' => ['sometimes', 'array'],
            'collections.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $store->getKey())],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateVariantDefaults($validator);
                $this->validateVariantPrices($validator);
                $this->validateVariantSkus($validator);
                $this->validateVariantIds($validator);
            },
        ];
    }

    private function routeStore(): Store
    {
        $store = $this->route('store');

        return $store instanceof Store ? $store : Store::query()->findOrFail($store);
    }

    private function routeProduct(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product : null;
    }

    private function validateVariantDefaults(Validator $validator): void
    {
        if (! $this->has('variants')) {
            return;
        }

        $variants = $this->input('variants', []);

        if (! is_array($variants)) {
            return;
        }

        $defaultCount = collect($variants)
            ->filter(fn (mixed $variant): bool => is_array($variant) && (bool) ($variant['is_default'] ?? false))
            ->count();

        if ($defaultCount !== 1) {
            $validator->errors()->add('variants', __('Exactly one product variant must be marked as default.'));
        }
    }

    private function validateVariantPrices(Validator $validator): void
    {
        foreach ($this->input('variants', []) as $index => $variant) {
            if (! is_array($variant) || ! isset($variant['compare_at_amount'])) {
                continue;
            }

            $compareAtAmount = $variant['compare_at_amount'];

            if ($compareAtAmount === null || $compareAtAmount === '') {
                continue;
            }

            if ((int) $compareAtAmount <= (int) ($variant['price_amount'] ?? 0)) {
                $validator->errors()->add("variants.{$index}.compare_at_amount", __('The compare at amount must be greater than the price amount.'));
            }
        }
    }

    private function validateVariantSkus(Validator $validator): void
    {
        if (! $this->has('variants')) {
            return;
        }

        $skus = collect($this->input('variants', []))
            ->map(fn (mixed $variant): string => is_array($variant) ? trim((string) ($variant['sku'] ?? '')) : '')
            ->filter()
            ->values();

        if ($skus->duplicates()->isNotEmpty()) {
            $validator->errors()->add('variants.0.sku', __('Each variant SKU must be unique for this store.'));

            return;
        }

        $product = $this->routeProduct();

        $conflictingSku = ProductVariant::withoutGlobalScopes()
            ->whereIn('sku', $skus->all())
            ->whereHas('product', function (Builder $query): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $this->routeStore()->getKey());
            })
            ->when($product instanceof Product, function (Builder $query) use ($product): void {
                $query->where('product_id', '!=', $product->getKey());
            })
            ->value('sku');

        if ($conflictingSku !== null) {
            $validator->errors()->add('variants.0.sku', __('The SKU [:sku] is already used in this store.', ['sku' => $conflictingSku]));
        }
    }

    private function validateVariantIds(Validator $validator): void
    {
        $product = $this->routeProduct();

        if (! $product instanceof Product) {
            return;
        }

        $variantIds = collect($this->input('variants', []))
            ->pluck('id')
            ->filter()
            ->map(fn (mixed $variantId): int => (int) $variantId)
            ->values();

        if ($variantIds->isEmpty()) {
            return;
        }

        $validCount = ProductVariant::withoutGlobalScopes()
            ->where('product_id', $product->getKey())
            ->whereIn('id', $variantIds->all())
            ->count();

        if ($validCount !== $variantIds->count()) {
            $validator->errors()->add('variants', __('Variant IDs must belong to the product being updated.'));
        }
    }
}
