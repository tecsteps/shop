<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    use UsesAdminStore;

    public ?Product $product = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public int $priceAmount = 0;

    public string $sku = '';

    public int $quantityOnHand = 0;

    public function mount(?Product $product = null): void
    {
        $this->product = $product?->exists ? $product->load('variants.inventoryItem') : null;

        if ($this->product === null) {
            Gate::authorize('create', Product::class);

            return;
        }

        Gate::authorize('update', $this->product);

        $variant = $this->product->variants->sortBy('position')->first();

        $this->title = $this->product->title;
        $this->handle = $this->product->handle;
        $this->descriptionHtml = $this->product->description_html ?? '';
        $this->status = $this->product->status->value;
        $this->vendor = $this->product->vendor ?? '';
        $this->productType = $this->product->product_type ?? '';
        $this->tags = implode(', ', $this->product->tags ?? []);
        $this->priceAmount = (int) ($variant?->price_amount ?? 0);
        $this->sku = $variant?->sku ?? '';
        $this->quantityOnHand = (int) ($variant?->inventoryItem?->quantity_on_hand ?? 0);
    }

    public function save(ProductService $products): mixed
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_map(fn (ProductStatus $status): string => $status->value, ProductStatus::cases()))],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'priceAmount' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'quantityOnHand' => ['required', 'integer', 'min:0'],
        ]);

        $payload = [
            'title' => $validated['title'],
            'handle' => $validated['handle'] ?: null,
            'description_html' => $validated['descriptionHtml'],
            'status' => $validated['status'],
            'vendor' => $validated['vendor'] ?: null,
            'product_type' => $validated['productType'] ?: null,
            'tags' => collect(explode(',', $validated['tags'] ?? ''))
                ->map(fn (string $tag): string => trim($tag))
                ->filter()
                ->values()
                ->all(),
            'price_amount' => $validated['priceAmount'],
        ];

        if ($this->product === null) {
            Gate::authorize('create', Product::class);
            $this->product = $products->create($this->currentStore(), $payload);
        } else {
            Gate::authorize('update', $this->product);
            $this->product = $products->update($this->product, $payload);
        }

        $variant = $this->product->variants()->oldest('position')->first()
            ?? $this->product->variants()->create([
                'price_amount' => $this->priceAmount,
                'currency' => $this->currentStore()->default_currency,
                'is_default' => true,
                'status' => VariantStatus::Active,
            ]);

        $variant->forceFill([
            'sku' => $this->sku ?: null,
            'price_amount' => $this->priceAmount,
            'currency' => $this->currentStore()->default_currency,
            'is_default' => true,
            'status' => VariantStatus::Active,
        ])->save();

        $variant->inventoryItem()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $this->currentStore()->id,
                'quantity_on_hand' => $this->quantityOnHand,
            ],
        );

        $this->notify('Product saved.');

        return $this->redirect(route('admin.products.edit', $this->product), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.products.form', [
            'statuses' => ProductStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => $this->product ? 'Edit product' : 'Create product',
        ]);
    }
}
