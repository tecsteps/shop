<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Product')]
class Form extends Component
{
    public ?Product $product = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public ?string $publishedAt = null;

    public string $sku = '';

    public int $priceAmount = 0;

    public int $quantity = 0;

    public function mount(?Product $product = null): void
    {
        $this->product = $product?->exists ? $product : null;

        if ($this->product) {
            Gate::authorize('update', $product);
            $product->load('variants.inventoryItem');
            $variant = $product->variants->first();
            $this->fill([
                'title' => $product->title, 'handle' => $product->handle,
                'descriptionHtml' => $product->description_html ?? '', 'status' => $product->status->value,
                'vendor' => $product->vendor ?? '', 'productType' => $product->product_type ?? '',
                'tags' => implode(', ', $product->tags ?? []),
                'publishedAt' => $product->published_at?->format('Y-m-d\TH:i'),
                'sku' => $variant?->sku ?? '', 'priceAmount' => $variant?->price_amount ?? 0,
                'quantity' => $variant?->inventoryItem?->quantity_on_hand ?? 0,
            ]);
        } else {
            Gate::authorize('create', Product::class);
        }
    }

    public function save(ProductService $service): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', Rule::unique('products', 'handle')->where('store_id', app('current_store')->id)->ignore($this->product?->id)],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'], 'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'vendor' => ['nullable', 'string', 'max:255'], 'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'], 'sku' => ['nullable', 'string', 'max:255'],
            'priceAmount' => ['required', 'integer', 'min:0'], 'quantity' => ['required', 'integer', 'min:0'],
        ]);
        $data = [
            'title' => $validated['title'], 'description_html' => $validated['descriptionHtml'],
            'status' => $validated['status'], 'vendor' => $validated['vendor'], 'product_type' => $validated['productType'],
            'tags' => array_values(array_filter(array_map('trim', explode(',', $validated['tags'])))),
            'sku' => $validated['sku'], 'price_amount' => $validated['priceAmount'], 'quantity_on_hand' => $validated['quantity'],
        ];
        if ($validated['handle'] !== '') {
            $data['handle'] = $validated['handle'];
        }

        if ($this->product) {
            Gate::authorize('update', $this->product);
            $this->product = $service->update($this->product, $data);
            $variant = $this->product->variants()->first();
            $variant?->update(['sku' => $this->sku ?: null, 'price_amount' => $this->priceAmount]);
            $variant?->inventoryItem?->update(['quantity_on_hand' => $this->quantity]);
        } else {
            Gate::authorize('create', Product::class);
            $this->product = $service->create(app('current_store'), $data);
        }

        session()->flash('toast', 'Product saved.');
        $this->redirectRoute('admin.products.edit', ['product' => $this->product], navigate: true);
    }

    public function archive(): void
    {
        abort_unless($this->product, 404);
        Gate::authorize('delete', $this->product);
        $this->product->update(['status' => 'archived']);
        $this->redirectRoute('admin.products.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.products.form');
    }
}
