<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var array<int> */
    public array $collectionIds = [];

    /** @var array<array{name: string, values: array<string>}> */
    public array $options = [];

    /** @var array<array{sku: string, price: int, compareAtPrice: int|null, quantity: int, requiresShipping: bool, optionValues: array<string>}> */
    public array $variants = [];

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newMedia = [];

    public bool $showSeo = false;

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
            $this->publishedAt = $product->published_at;
            $this->collectionIds = $product->collections()->pluck('collections.id')->toArray();

            $this->options = $product->options->map(fn ($opt) => [
                'name' => $opt->name,
                'values' => $opt->values()->orderBy('position')->pluck('value')->toArray(),
            ])->toArray();

            $this->variants = $product->variants->map(fn ($v) => [
                'sku' => $v->sku ?? '',
                'price' => $v->price_amount,
                'compareAtPrice' => $v->compare_at_amount,
                'quantity' => $v->inventoryItem?->quantity_on_hand ?? 0,
                'requiresShipping' => (bool) $v->requires_shipping,
                'optionValues' => $v->optionValues()->pluck('value')->toArray(),
            ])->toArray();
        }

        if (empty($this->variants)) {
            $this->variants = [
                ['sku' => '', 'price' => 0, 'compareAtPrice' => null, 'quantity' => 0, 'requiresShipping' => true, 'optionValues' => []],
            ];
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->isEditing) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function addOption(): void
    {
        $this->options[] = ['name' => '', 'values' => ['']];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->generateVariants();
    }

    public function addOptionValue(int $optionIndex): void
    {
        $this->options[$optionIndex]['values'][] = '';
    }

    public function removeOptionValue(int $optionIndex, int $valueIndex): void
    {
        unset($this->options[$optionIndex]['values'][$valueIndex]);
        $this->options[$optionIndex]['values'] = array_values($this->options[$optionIndex]['values']);
        $this->generateVariants();
    }

    public function generateVariants(): void
    {
        $filteredOptions = array_filter($this->options, function ($opt) {
            return $opt['name'] !== '' && count(array_filter($opt['values'], fn ($v) => $v !== '')) > 0;
        });

        if (empty($filteredOptions)) {
            if (empty($this->variants)) {
                $this->variants = [
                    ['sku' => '', 'price' => 0, 'compareAtPrice' => null, 'quantity' => 0, 'requiresShipping' => true, 'optionValues' => []],
                ];
            }

            return;
        }

        $valueSets = array_map(fn ($opt) => array_filter($opt['values'], fn ($v) => $v !== ''), $filteredOptions);
        $combinations = $this->cartesianProduct($valueSets);

        $this->variants = array_map(fn ($combo) => [
            'sku' => '',
            'price' => 0,
            'compareAtPrice' => null,
            'quantity' => 0,
            'requiresShipping' => true,
            'optionValues' => is_array($combo) ? $combo : [$combo],
        ], $combinations);
    }

    public function removeMedia(int $mediaId): void
    {
        ProductMedia::where('id', $mediaId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Media removed.');
    }

    public function save(): void
    {
        $storeId = app('current_store')->id;

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => [
                'required', 'string', 'max:255',
                Rule::unique('products', 'handle')
                    ->where('store_id', $storeId)
                    ->ignore($this->product?->id),
            ],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
        ];

        $this->validate($rules);

        $productData = [
            'store_id' => $storeId,
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->productType ?: null,
            'tags' => $this->tags ? array_map('trim', explode(',', $this->tags)) : [],
            'published_at' => $this->publishedAt,
        ];

        if ($this->product && $this->product->exists) {
            $this->product->update($productData);
            $product = $this->product;
        } else {
            $product = Product::withoutGlobalScopes()->create($productData);
            $this->product = $product;
        }

        // Sync collections
        $product->collections()->sync($this->collectionIds);

        // Sync options
        $product->options()->delete();
        foreach ($this->options as $pos => $opt) {
            if ($opt['name'] === '') {
                continue;
            }
            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $opt['name'],
                'position' => $pos,
            ]);
            foreach ($opt['values'] as $vPos => $val) {
                if ($val === '') {
                    continue;
                }
                ProductOptionValue::create([
                    'product_option_id' => $option->id,
                    'value' => $val,
                    'position' => $vPos,
                ]);
            }
        }

        // Sync variants
        $product->variants()->each(function ($variant) {
            $variant->inventoryItem?->delete();
            $variant->optionValues()->detach();
            $variant->delete();
        });

        foreach ($this->variants as $pos => $variantData) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'] ?: null,
                'price_amount' => $variantData['price'],
                'compare_at_amount' => $variantData['compareAtPrice'],
                'requires_shipping' => $variantData['requiresShipping'],
                'is_default' => $pos === 0,
                'position' => $pos,
                'status' => 'active',
                'currency' => 'USD',
            ]);

            $variant->inventoryItem()->create([
                'store_id' => $storeId,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $variantData['quantity'],
                'quantity_reserved' => 0,
                'policy' => 'deny',
            ]);
        }

        // Handle file uploads
        foreach ($this->newMedia as $file) {
            $path = $file->store('product-media', 'public');
            ProductMedia::create([
                'product_id' => $product->id,
                'type' => 'image',
                'storage_key' => $path,
                'alt_text' => '',
                'mime_type' => $file->getMimeType(),
                'byte_size' => $file->getSize(),
                'position' => $product->media()->count(),
                'status' => 'active',
                'created_at' => now()->toIso8601String(),
            ]);
        }
        $this->newMedia = [];

        $this->dispatch('toast', type: 'success', message: 'Product saved.');
    }

    public function deleteProduct(): void
    {
        if ($this->product) {
            $this->product->update(['status' => ProductStatus::Archived->value]);
            $this->dispatch('toast', type: 'success', message: 'Product archived.');
            $this->redirect(route('admin.products.index'), navigate: true);
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product !== null && $this->product->exists;
    }

    #[Computed]
    public function availableCollections(): mixed
    {
        return Collection::query()->get();
    }

    #[Computed]
    public function existingMedia(): mixed
    {
        if ($this->product && $this->product->exists) {
            return $this->product->media()->orderBy('position')->get();
        }

        return collect();
    }

    public function render(): mixed
    {
        $breadcrumbs = [
            ['label' => 'Products', 'url' => route('admin.products.index')],
        ];

        if ($this->isEditing) {
            $breadcrumbs[] = ['label' => $this->product->title];
        } else {
            $breadcrumbs[] = ['label' => 'Add product'];
        }

        return view('livewire.admin.products.form')
            ->layout('layouts.admin', [
                'breadcrumbs' => $breadcrumbs,
            ]);
    }

    /**
     * @param  array<array<string>>  $arrays
     * @return array<array<string>>
     */
    protected function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $array) {
            $append = [];
            foreach ($result as $current) {
                foreach ($array as $item) {
                    $append[] = array_merge($current, [$item]);
                }
            }
            $result = $append;
        }

        return $result;
    }
}
