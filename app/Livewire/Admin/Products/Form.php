<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:65535')]
    public string $descriptionHtml = '';

    #[Validate('required|string|in:draft,active,archived')]
    public string $status = 'draft';

    #[Validate('nullable|string|max:255')]
    public string $vendor = '';

    #[Validate('nullable|string|max:255')]
    public string $productType = '';

    #[Validate('nullable|string')]
    public string $tags = '';

    #[Validate('required|string|max:255')]
    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var array<int> */
    public array $collectionIds = [];

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array{sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool, label: string}> */
    public array $variants = [];

    public bool $showSeo = false;

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->authorize('update', $product);
            $this->product = $product;
            $this->title = $product->title;
            $this->descriptionHtml = $product->description_html ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->productType = $product->product_type ?? '';
            $this->tags = is_array($product->tags) ? implode(', ', $product->tags) : '';
            $this->handle = $product->handle;
            $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
            $this->collectionIds = $product->collections->pluck('id')->toArray();

            $product->load(['options.values', 'variants.optionValues', 'variants.inventoryItem']);

            foreach ($product->options as $option) {
                $this->options[] = [
                    'name' => $option->name,
                    'values' => $option->values->pluck('value')->implode(', '),
                ];
            }

            foreach ($product->variants as $variant) {
                $label = $variant->optionValues->pluck('value')->implode(' / ') ?: 'Default';
                $this->variants[] = [
                    'sku' => $variant->sku ?? '',
                    'price' => (string) ($variant->price_amount / 100),
                    'compareAtPrice' => $variant->compare_at_amount ? (string) ($variant->compare_at_amount / 100) : '',
                    'quantity' => (string) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                    'requiresShipping' => $variant->requires_shipping,
                    'label' => $label,
                ];
            }
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product !== null && $this->product->exists;
    }

    #[Computed]
    public function availableCollections(): \Illuminate\Database\Eloquent\Collection
    {
        $store = app('current_store');

        return Collection::query()
            ->where('store_id', $store->id)
            ->orderBy('title')
            ->get();
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
            $values = array_filter($values);
            if (! empty($values)) {
                $optionSets[] = $values;
            }
        }

        if (empty($optionSets)) {
            $this->variants = [];

            return;
        }

        $combinations = $this->cartesianProduct($optionSets);
        $existingVariants = collect($this->variants)->keyBy('label');

        $this->variants = [];
        foreach ($combinations as $combo) {
            $label = implode(' / ', $combo);
            $existing = $existingVariants->get($label);
            $this->variants[] = [
                'sku' => $existing['sku'] ?? '',
                'price' => $existing['price'] ?? '0',
                'compareAtPrice' => $existing['compareAtPrice'] ?? '',
                'quantity' => $existing['quantity'] ?? '0',
                'requiresShipping' => $existing['requiresShipping'] ?? true,
                'label' => $label,
            ];
        }
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            $this->authorize('update', $this->product);
        } else {
            $this->authorize('create', Product::class);
        }

        $store = app('current_store');

        if (empty($this->handle)) {
            $this->handle = Str::slug($this->title);
        }

        $productData = [
            'store_id' => $store->id,
            'title' => $this->title,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->productType ?: null,
            'tags' => $this->tags ? array_map('trim', explode(',', $this->tags)) : [],
            'handle' => $this->handle,
            'published_at' => $this->publishedAt ? \Carbon\Carbon::parse($this->publishedAt) : null,
        ];

        if ($this->isEditing) {
            $this->product->update($productData);
            $product = $this->product;
        } else {
            $product = Product::create($productData);
            $this->product = $product;
        }

        // Sync collections
        $product->collections()->sync($this->collectionIds);

        // Sync options and variants
        $this->syncOptionsAndVariants($product);

        $this->dispatch('toast', type: 'success', message: $this->isEditing ? 'Product updated.' : 'Product created.');

        if (! $this->isEditing) {
            $this->redirect(route('admin.products.edit', $product), navigate: true);
        }
    }

    public function deleteProduct(): void
    {
        if ($this->product) {
            $this->authorize('delete', $this->product);
            $this->product->update(['status' => ProductStatus::Archived]);
            $this->dispatch('toast', type: 'success', message: 'Product archived.');
            $this->redirect(route('admin.products.index'), navigate: true);
        }
    }

    protected function syncOptionsAndVariants(Product $product): void
    {
        // Delete old options/variants and recreate
        $product->options()->each(function ($option): void {
            $option->values()->delete();
        });
        $product->options()->delete();

        $optionModels = [];
        foreach ($this->options as $optionData) {
            if (empty($optionData['name'])) {
                continue;
            }
            $option = $product->options()->create([
                'name' => $optionData['name'],
                'position' => count($optionModels) + 1,
            ]);
            $values = array_map('trim', explode(',', $optionData['values']));
            foreach ($values as $position => $value) {
                if (empty($value)) {
                    continue;
                }
                $option->values()->create([
                    'value' => $value,
                    'position' => $position + 1,
                ]);
            }
            $optionModels[] = $option;
        }

        // Delete existing variants
        $product->variants()->each(function ($variant): void {
            $variant->inventoryItem()?->delete();
            $variant->optionValues()->detach();
        });
        $product->variants()->delete();

        // Create new variants
        if (empty($this->variants)) {
            $variant = $product->variants()->create([
                'sku' => null,
                'price_amount' => 0,
                'compare_at_amount' => null,
                'requires_shipping' => true,
                'is_default' => true,
                'position' => 1,
            ]);
            $variant->inventoryItem()->create([
                'store_id' => $product->store_id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
            ]);
        } else {
            foreach ($this->variants as $position => $variantData) {
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'] ?: null,
                    'price_amount' => (int) round((float) $variantData['price'] * 100),
                    'compare_at_amount' => ! empty($variantData['compareAtPrice']) ? (int) round((float) $variantData['compareAtPrice'] * 100) : null,
                    'requires_shipping' => $variantData['requiresShipping'],
                    'is_default' => $position === 0,
                    'position' => $position + 1,
                ]);

                // Attach option values
                $valueParts = array_map('trim', explode('/', $variantData['label']));
                foreach ($valueParts as $optionIdx => $valueName) {
                    if (isset($optionModels[$optionIdx])) {
                        $optionValue = $optionModels[$optionIdx]->values()->where('value', trim($valueName))->first();
                        if ($optionValue) {
                            $variant->optionValues()->attach($optionValue->id);
                        }
                    }
                }

                $variant->inventoryItem()->create([
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => (int) ($variantData['quantity'] ?? 0),
                    'quantity_reserved' => 0,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<int, string>>  $arrays
     * @return array<int, array<int, string>>
     */
    protected function cartesianProduct(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $values) {
            $newResult = [];
            foreach ($result as $combo) {
                foreach ($values as $value) {
                    $newResult[] = [...$combo, $value];
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    #[Title('Product')]
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.products.form');
    }
}
