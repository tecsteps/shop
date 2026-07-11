<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Form extends AdminComponent
{
    public ?int $productId = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var list<int> */
    public array $collectionIds = [];

    /** @var list<array<string, mixed>> */
    public array $variants = [['sku' => '', 'price' => 0, 'compareAtPrice' => null, 'quantity' => 0, 'requiresShipping' => true]];

    public function mount(?Product $product = null): void
    {
        if ($product === null || ! $product->exists) {
            Gate::authorize('create', Product::class);

            return;
        }
        Gate::authorize('update', $product);
        $product->load(['variants.inventoryItem', 'collections']);
        $this->productId = $product->getKey();
        $this->title = $product->title;
        $this->descriptionHtml = $product->description_html ?? '';
        $this->status = $product->status->value;
        $this->vendor = $product->vendor ?? '';
        $this->productType = $product->product_type ?? '';
        $this->tags = implode(', ', $product->tags ?? []);
        $this->handle = $product->handle;
        $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
        $this->collectionIds = $product->collections->modelKeys();
        $this->variants = $product->variants->map(fn ($variant): array => [
            'id' => $variant->id, 'sku' => $variant->sku ?? '', 'price' => $variant->price_amount,
            'compareAtPrice' => $variant->compare_at_amount, 'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
            'requiresShipping' => $variant->requires_shipping,
        ])->all();
    }

    public function updatedTitle(): void
    {
        if ($this->productId === null) {
            $this->handle = Str::slug($this->title);
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = ['sku' => '', 'price' => 0, 'compareAtPrice' => null, 'quantity' => 0, 'requiresShipping' => true];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(ProductService $service): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'], 'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::enum(ProductStatus::class)], 'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'], 'tags' => ['nullable', 'string'], 'handle' => ['required', 'string', 'max:255'],
            'publishedAt' => ['nullable', 'date'], 'collectionIds' => ['array'], 'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'], 'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'integer', 'min:0'], 'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.requiresShipping' => ['boolean'],
        ]);
        $payload = [
            'title' => $validated['title'], 'description_html' => $validated['descriptionHtml'], 'status' => $validated['status'],
            'vendor' => $validated['vendor'], 'product_type' => $validated['productType'],
            'tags' => collect(explode(',', $validated['tags']))->map(fn (string $tag): string => trim($tag))->filter()->values()->all(),
            'handle' => $validated['handle'], 'published_at' => $validated['publishedAt'], 'collection_ids' => $validated['collectionIds'],
            'variants' => collect($validated['variants'])->map(fn (array $variant): array => [
                'id' => $variant['id'] ?? null, 'sku' => $variant['sku'] ?: null, 'price_amount' => $variant['price'],
                'compare_at_amount' => $variant['compareAtPrice'], 'requires_shipping' => $variant['requiresShipping'],
                'inventory' => ['quantity_on_hand' => $variant['quantity']],
            ])->all(),
        ];
        if ($this->productId === null) {
            Gate::authorize('create', Product::class);
            $product = $service->create($this->currentStore(), $payload);
            $this->productId = $product->getKey();
        } else {
            $product = Product::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->productId);
            Gate::authorize('update', $product);
            $service->update($product, $payload);
        }
        $this->toast('Product saved.');
    }

    public function archive(ProductService $service): void
    {
        $product = Product::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->productId);
        Gate::authorize('archive', $product);
        $service->transitionStatus($product, ProductStatus::Archived);
        $this->status = ProductStatus::Archived->value;
        $this->toast('Product archived.');
    }

    #[Computed]
    public function availableCollections()
    {
        return Collection::query()->where('store_id', $this->currentStore()->getKey())->orderBy('title')->get();
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->productId !== null;
    }

    public function render()
    {
        return view('livewire.admin.products.form');
    }
}
