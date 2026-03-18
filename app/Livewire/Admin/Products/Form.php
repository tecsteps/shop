<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    /** @var array<int> */
    public array $collectionIds = [];

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array{sku: string, price: int, compareAtPrice: ?int, quantity: int, requiresShipping: bool, optionValues: string}> */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product;
            $this->title = $product->title;
            $this->descriptionHtml = $product->description_html ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->productType = $product->product_type ?? '';
            $this->tags = is_array($product->tags) ? implode(', ', $product->tags) : '';
            $this->handle = $product->handle;
            $this->collectionIds = $product->collections()->pluck('collections.id')->toArray();

            $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues']);

            $this->options = $product->options->map(fn (ProductOption $option) => [
                'name' => $option->name,
                'values' => $option->values->pluck('value')->implode(', '),
            ])->toArray();

            $this->variants = $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku ?? '',
                'price' => $variant->price_amount,
                'compareAtPrice' => $variant->compare_at_amount,
                'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                'requiresShipping' => $variant->requires_shipping ?? true,
                'optionValues' => $variant->optionValues->count() > 0
                    ? $variant->optionValues->pluck('value')->implode(' / ')
                    : 'Default',
            ])->toArray();
        }
    }

    public function addOption(): void
    {
        $this->options[] = ['name' => '', 'values' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->generateVariants();
    }

    public function generateVariants(): void
    {
        $optionSets = [];

        foreach ($this->options as $option) {
            if (empty($option['name']) || empty($option['values'])) {
                continue;
            }
            $values = array_map('trim', explode(',', $option['values']));
            $values = array_filter($values, fn ($v) => $v !== '');
            if (! empty($values)) {
                $optionSets[] = $values;
            }
        }

        if (empty($optionSets)) {
            if (empty($this->variants)) {
                $this->variants = [[
                    'sku' => '',
                    'price' => 0,
                    'compareAtPrice' => null,
                    'quantity' => 0,
                    'requiresShipping' => true,
                    'optionValues' => 'Default',
                ]];
            }

            return;
        }

        $combinations = $this->cartesianProduct($optionSets);

        $this->variants = array_map(fn ($combo) => [
            'sku' => '',
            'price' => 0,
            'compareAtPrice' => null,
            'quantity' => 0,
            'requiresShipping' => true,
            'optionValues' => implode(' / ', $combo),
        ], $combinations);
    }

    public function save(): void
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,active,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
        ];

        $this->validate($rules);

        $store = app('current_store');

        if (! $this->handle) {
            $this->handle = Str::slug($this->title);
        }

        $tagsArray = $this->tags
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $productData = [
            'store_id' => $store->id,
            'title' => $this->title,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->productType ?: null,
            'tags' => $tagsArray,
            'handle' => $this->handle,
            'published_at' => $this->status === 'active' ? now() : null,
        ];

        if ($this->product?->exists) {
            $this->product->update($productData);
            $product = $this->product;
        } else {
            $product = Product::create($productData);
        }

        $this->syncOptions($product);
        $this->syncVariants($product);

        $product->collections()->sync($this->collectionIds);

        $this->dispatch('toast', type: 'success', message: $this->isEditing
            ? __('Product updated.')
            : __('Product created.')
        );

        $this->redirect(route('admin.products.edit', $product), navigate: true);
    }

    public function deleteProduct(): void
    {
        if ($this->product?->exists) {
            $this->product->update(['status' => ProductStatus::Archived]);
            $this->dispatch('toast', type: 'success', message: __('Product archived.'));
            $this->redirect(route('admin.products.index'), navigate: true);
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product?->exists ?? false;
    }

    #[Computed]
    public function availableCollections(): \Illuminate\Database\Eloquent\Collection
    {
        $store = app('current_store');

        return Collection::where('store_id', $store->id)->orderBy('title')->get();
    }

    protected function syncOptions(Product $product): void
    {
        $product->options()->delete();

        foreach ($this->options as $position => $optionData) {
            if (empty($optionData['name'])) {
                continue;
            }

            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $optionData['name'],
                'position' => $position + 1,
            ]);

            $values = array_map('trim', explode(',', $optionData['values']));
            foreach ($values as $valPos => $value) {
                if ($value === '') {
                    continue;
                }
                ProductOptionValue::create([
                    'option_id' => $option->id,
                    'value' => $value,
                    'position' => $valPos + 1,
                ]);
            }
        }
    }

    protected function syncVariants(Product $product): void
    {
        $existingVariantIds = $product->variants()->pluck('id')->toArray();
        $processedIds = [];

        foreach ($this->variants as $variantData) {
            if (isset($variantData['id']) && in_array($variantData['id'], $existingVariantIds)) {
                $variant = ProductVariant::find($variantData['id']);
                $variant->update([
                    'sku' => $variantData['sku'] ?: null,
                    'price_amount' => (int) $variantData['price'],
                    'compare_at_amount' => $variantData['compareAtPrice'] ? (int) $variantData['compareAtPrice'] : null,
                    'requires_shipping' => $variantData['requiresShipping'],
                ]);

                if ($variant->inventoryItem) {
                    $variant->inventoryItem->update([
                        'quantity_on_hand' => (int) $variantData['quantity'],
                    ]);
                }

                $processedIds[] = $variant->id;
            } else {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'] ?: null,
                    'price_amount' => (int) $variantData['price'],
                    'compare_at_amount' => $variantData['compareAtPrice'] ? (int) $variantData['compareAtPrice'] : null,
                    'requires_shipping' => $variantData['requiresShipping'],
                    'position' => 1,
                ]);

                $variant->inventoryItem()->create([
                    'sku' => $variantData['sku'] ?: null,
                    'quantity_on_hand' => (int) $variantData['quantity'],
                    'quantity_committed' => 0,
                ]);

                $processedIds[] = $variant->id;
            }
        }

        $toDelete = array_diff($existingVariantIds, $processedIds);
        if (! empty($toDelete)) {
            ProductVariant::whereIn('id', $toDelete)->delete();
        }
    }

    /** @return array<int, array<int, string>> */
    protected function cartesianProduct(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $temp = [];
            foreach ($result as $prefix) {
                foreach ($set as $value) {
                    $temp[] = array_merge($prefix, [$value]);
                }
            }
            $result = $temp;
        }

        return $result;
    }

    public function render(): View
    {
        return view('livewire.admin.products.form');
    }
}
