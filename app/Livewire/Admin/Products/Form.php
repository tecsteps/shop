<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array{title: string, sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool}> */
    public array $variants = [];

    /** @var array<int, array{id: int, url: string, alt_text: string, position: int}> */
    public array $existingMedia = [];

    /** @var array<mixed> */
    public array $newMedia = [];

    public bool $showSeo = false;

    public bool $showDeleteModal = false;

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product->load(['options.values', 'variants.inventoryItem', 'media', 'collections']);
            $this->title = $product->title;
            $this->descriptionHtml = $product->description_html ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->productType = $product->product_type ?? '';
            $this->tags = is_array($product->tags) ? implode(', ', $product->tags) : ($product->tags ?? '');
            $this->handle = $product->handle;
            $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
            $this->collectionIds = $product->collections->pluck('id')->all();

            foreach ($product->options as $option) {
                $this->options[] = [
                    'name' => $option->name,
                    'values' => $option->values->pluck('value')->implode(', '),
                ];
            }

            foreach ($product->variants as $variant) {
                $this->variants[] = [
                    'title' => $variant->title,
                    'sku' => $variant->sku ?? '',
                    'price' => (string) ($variant->price_amount / 100),
                    'compareAtPrice' => $variant->compare_at_price_amount ? (string) ($variant->compare_at_price_amount / 100) : '',
                    'quantity' => (string) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                    'requiresShipping' => $variant->requires_shipping ?? true,
                ];
            }

            $this->existingMedia = $product->media->sortBy('position')->map(fn (ProductMedia $m) => [
                'id' => $m->id,
                'url' => $m->url,
                'alt_text' => $m->alt_text ?? '',
                'position' => $m->position,
            ])->values()->all();
        } else {
            $this->variants = [
                [
                    'title' => 'Default',
                    'sku' => '',
                    'price' => '0',
                    'compareAtPrice' => '',
                    'quantity' => '0',
                    'requiresShipping' => true,
                ],
            ];
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
            $values = array_filter(array_map('trim', explode(',', $option['values'])));
            if (empty($values)) {
                continue;
            }
            $optionSets[] = $values;
        }

        if (empty($optionSets)) {
            $this->variants = [
                [
                    'title' => 'Default',
                    'sku' => '',
                    'price' => '0',
                    'compareAtPrice' => '',
                    'quantity' => '0',
                    'requiresShipping' => true,
                ],
            ];

            return;
        }

        $combinations = [[]];
        foreach ($optionSets as $set) {
            $tmp = [];
            foreach ($combinations as $existing) {
                foreach ($set as $value) {
                    $tmp[] = array_merge($existing, [$value]);
                }
            }
            $combinations = $tmp;
        }

        $existingVariants = collect($this->variants)->keyBy('title');

        $this->variants = [];
        foreach ($combinations as $combo) {
            $variantTitle = implode(' / ', $combo);
            $existing = $existingVariants->get($variantTitle);

            $this->variants[] = [
                'title' => $variantTitle,
                'sku' => $existing['sku'] ?? '',
                'price' => $existing['price'] ?? '0',
                'compareAtPrice' => $existing['compareAtPrice'] ?? '',
                'quantity' => $existing['quantity'] ?? '0',
                'requiresShipping' => $existing['requiresShipping'] ?? true,
            ];
        }
    }

    public function removeMedia(int $mediaId): void
    {
        ProductMedia::where('id', $mediaId)->delete();
        $this->existingMedia = array_values(
            array_filter($this->existingMedia, fn ($m) => $m['id'] !== $mediaId)
        );
        $this->dispatch('toast', type: 'success', message: 'Image removed.');
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,active,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $productService = app(ProductService::class);
        $variantMatrixService = app(VariantMatrixService::class);

        DB::transaction(function () use ($productService, $variantMatrixService) {
            $tags = $this->tags
                ? array_map('trim', explode(',', $this->tags))
                : null;

            if ($this->product && $this->product->exists) {
                $this->authorize('update', $this->product);

                $this->product->update([
                    'title' => $this->title,
                    'description_html' => $this->descriptionHtml ?: null,
                    'status' => $this->status,
                    'vendor' => $this->vendor ?: null,
                    'product_type' => $this->productType ?: null,
                    'tags' => $tags,
                    'handle' => $this->handle ?: Str::slug($this->title),
                    'published_at' => $this->publishedAt ? \Carbon\Carbon::parse($this->publishedAt) : null,
                ]);
            } else {
                $this->authorize('create', Product::class);

                $this->product = $productService->create(
                    app('current_store'),
                    [
                        'title' => $this->title,
                        'description_html' => $this->descriptionHtml ?: null,
                        'status' => ProductStatus::from($this->status),
                        'vendor' => $this->vendor ?: null,
                        'product_type' => $this->productType ?: null,
                        'tags' => $tags,
                        'price_amount' => (int) round(($this->variants[0]['price'] ?? 0) * 100),
                        'sku' => $this->variants[0]['sku'] ?? null,
                        'quantity_on_hand' => (int) ($this->variants[0]['quantity'] ?? 0),
                    ]
                );

                if ($this->handle) {
                    $this->product->update(['handle' => $this->handle]);
                }
            }

            // Sync options and rebuild variant matrix
            if (! empty($this->options)) {
                $this->product->options()->delete();

                $position = 1;
                foreach ($this->options as $option) {
                    $opt = $this->product->options()->create([
                        'name' => $option['name'],
                        'position' => $position++,
                    ]);

                    $valPosition = 1;
                    $values = array_filter(array_map('trim', explode(',', $option['values'])));
                    foreach ($values as $value) {
                        $opt->values()->create([
                            'value' => $value,
                            'position' => $valPosition++,
                        ]);
                    }
                }

                $variantMatrixService->rebuildMatrix($this->product);
            }

            // Update variant data
            $this->product->load('variants.inventoryItem');
            foreach ($this->product->variants as $index => $variant) {
                if (! isset($this->variants[$index])) {
                    continue;
                }
                $data = $this->variants[$index];

                $variant->update([
                    'sku' => $data['sku'] ?: null,
                    'price_amount' => (int) round(((float) $data['price']) * 100),
                    'compare_at_price_amount' => $data['compareAtPrice'] !== '' ? (int) round(((float) $data['compareAtPrice']) * 100) : null,
                    'requires_shipping' => $data['requiresShipping'],
                ]);

                if ($variant->inventoryItem) {
                    $variant->inventoryItem->update([
                        'quantity_on_hand' => (int) $data['quantity'],
                    ]);
                }
            }

            // Sync collections
            $this->product->collections()->sync(
                collect($this->collectionIds)->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i + 1]])->all()
            );

            // Handle new media uploads
            foreach ($this->newMedia as $file) {
                $path = $file->store('products', 'public');
                $this->product->media()->create([
                    'type' => 'image',
                    'url' => '/storage/'.$path,
                    'alt_text' => null,
                    'position' => $this->product->media()->count(),
                    'status' => 'active',
                ]);
            }
            $this->newMedia = [];
        });

        $this->dispatch('toast', type: 'success', message: 'Product saved successfully.');
        $this->redirect(route('admin.products.edit', $this->product), navigate: true);
    }

    public function deleteProduct(): void
    {
        if (! $this->product) {
            return;
        }

        $this->authorize('delete', $this->product);

        try {
            app(ProductService::class)->delete($this->product);
            $this->dispatch('toast', type: 'success', message: 'Product deleted.');
            $this->redirect(route('admin.products.index'), navigate: true);
        } catch (\InvalidArgumentException $e) {
            $this->product->update(['status' => ProductStatus::Archived]);
            $this->dispatch('toast', type: 'info', message: 'Product archived (cannot be deleted due to existing orders).');
            $this->redirect(route('admin.products.index'), navigate: true);
        }
    }

    #[Computed]
    public function availableCollections(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()->orderBy('title')->get();
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product !== null && $this->product->exists;
    }

    public function render()
    {
        return view('livewire.admin.products.form')
            ->layout('layouts.admin', ['title' => $this->isEditing ? 'Edit Product' : 'Add Product']);
    }
}
