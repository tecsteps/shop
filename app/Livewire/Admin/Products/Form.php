<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    #[Validate('required|string|max:255')]
    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var array<int> */
    public array $collectionIds = [];

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array{sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool, optionValues: string}> */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
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

            $this->options = $product->options->map(fn (ProductOption $o) => [
                'name' => $o->name,
                'values' => $o->values->pluck('value')->implode(', '),
            ])->toArray();

            $this->variants = $product->variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'sku' => $v->sku ?? '',
                'price' => (string) ($v->price_amount / 100),
                'compareAtPrice' => $v->compare_at_amount ? (string) ($v->compare_at_amount / 100) : '',
                'quantity' => (string) ($v->inventoryItem?->quantity_on_hand ?? 0),
                'requiresShipping' => $v->requires_shipping,
                'optionValues' => $v->optionValues->pluck('value')->implode(' / ') ?: 'Default',
            ])->toArray();
        } else {
            $this->variants = [[
                'id' => null,
                'sku' => '',
                'price' => '0',
                'compareAtPrice' => '',
                'quantity' => '0',
                'requiresShipping' => true,
                'optionValues' => 'Default',
            ]];
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->product) {
            $this->handle = Str::slug($this->title);
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
            $values = array_map('trim', explode(',', $option['values']));
            $values = array_filter($values);
            if (! empty($values)) {
                $optionSets[] = $values;
            }
        }

        if (empty($optionSets)) {
            $this->variants = [[
                'id' => null,
                'sku' => '',
                'price' => '0',
                'compareAtPrice' => '',
                'quantity' => '0',
                'requiresShipping' => true,
                'optionValues' => 'Default',
            ]];

            return;
        }

        $combinations = [['']];
        foreach ($optionSets as $values) {
            $newCombinations = [];
            foreach ($combinations as $combo) {
                foreach ($values as $value) {
                    $newCombinations[] = array_filter(array_merge($combo, [$value]));
                }
            }
            $combinations = $newCombinations;
        }

        $this->variants = array_map(fn ($combo) => [
            'id' => null,
            'sku' => '',
            'price' => '0',
            'compareAtPrice' => '',
            'quantity' => '0',
            'requiresShipping' => true,
            'optionValues' => implode(' / ', $combo),
        ], $combinations);
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'handle' => 'required|string|max:255',
            'status' => 'required|in:draft,active,archived',
        ]);

        $tagsArray = $this->tags
            ? array_map('trim', explode(',', $this->tags))
            : [];

        $productData = [
            'store_id' => session('store_id'),
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->productType ?: null,
            'tags' => $tagsArray,
            'published_at' => $this->publishedAt ? \Carbon\Carbon::parse($this->publishedAt) : null,
        ];

        if ($this->product) {
            $this->product->update($productData);
        } else {
            $this->product = Product::withoutGlobalScopes()->create($productData);
        }

        // Sync options
        $this->product->options()->delete();
        foreach ($this->options as $position => $option) {
            $productOption = $this->product->options()->create([
                'name' => $option['name'],
                'position' => $position,
            ]);

            $values = array_map('trim', explode(',', $option['values']));
            foreach (array_filter($values) as $valPos => $value) {
                ProductOptionValue::create([
                    'product_option_id' => $productOption->id,
                    'value' => $value,
                    'position' => $valPos,
                ]);
            }
        }

        // Sync variants
        $existingVariantIds = $this->product->variants()->pluck('id')->toArray();
        $keptVariantIds = [];

        foreach ($this->variants as $position => $variantData) {
            $optionParts = array_map('trim', explode('/', $variantData['optionValues']));

            $variant = $variantData['id']
                ? ProductVariant::withoutGlobalScopes()->find($variantData['id'])
                : null;

            $variantAttrs = [
                'product_id' => $this->product->id,
                'sku' => $variantData['sku'] ?: null,
                'price_amount' => (int) round((float) $variantData['price'] * 100),
                'compare_at_amount' => $variantData['compareAtPrice'] ? (int) round((float) $variantData['compareAtPrice'] * 100) : null,
                'requires_shipping' => $variantData['requiresShipping'],
                'is_default' => $position === 0,
                'position' => $position,
            ];

            if ($variant) {
                $variant->update($variantAttrs);
                $keptVariantIds[] = $variant->id;
            } else {
                $variant = ProductVariant::create($variantAttrs);
                $keptVariantIds[] = $variant->id;
            }

            // Update or create inventory
            $inventoryItem = InventoryItem::withoutGlobalScopes()
                ->where('variant_id', $variant->id)
                ->first();

            if ($inventoryItem) {
                $inventoryItem->update([
                    'quantity_on_hand' => (int) $variantData['quantity'],
                ]);
            } else {
                InventoryItem::create([
                    'variant_id' => $variant->id,
                    'store_id' => session('store_id'),
                    'quantity_on_hand' => (int) $variantData['quantity'],
                    'quantity_reserved' => 0,
                ]);
            }
        }

        // Delete removed variants
        $deleteIds = array_diff($existingVariantIds, $keptVariantIds);
        if (! empty($deleteIds)) {
            ProductVariant::withoutGlobalScopes()->whereIn('id', $deleteIds)->delete();
        }

        // Sync collections
        $this->product->collections()->sync(
            collect($this->collectionIds)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->toArray()
        );

        $this->dispatch('toast', type: 'success', message: 'Product saved.');

        if (! $this->product->wasRecentlyCreated) {
            return;
        }

        $this->redirect(route('admin.products.edit', $this->product), navigate: true);
    }

    public function deleteProduct(): void
    {
        if ($this->product) {
            $this->product->update(['status' => ProductStatus::Archived]);
            $this->dispatch('toast', type: 'success', message: 'Product archived.');
            $this->redirect(route('admin.products.index'), navigate: true);
        }
    }

    public function getAvailableCollectionsProperty()
    {
        return Collection::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->orderBy('title')
            ->get();
    }

    public function getIsEditingProperty(): bool
    {
        return $this->product && $this->product->exists;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.products.form');
    }
}
