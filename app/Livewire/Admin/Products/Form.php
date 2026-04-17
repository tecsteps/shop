<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class Form extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $description_html = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $product_type = '';

    public string $tags = '';

    public int $price_amount = 0;

    public ?int $compare_at_price = null;

    public string $sku = '';

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $media = [];

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->authorize('update', Product::class);
            $this->product = $product;
            $this->title = $product->title;
            $this->description_html = $product->description_html ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->product_type = $product->product_type ?? '';
            $this->tags = is_array($product->tags) ? implode(', ', $product->tags) : '';

            $defaultVariant = $product->variants()->where('is_default', true)->first();
            if ($defaultVariant) {
                $this->price_amount = $defaultVariant->price_amount;
                $this->compare_at_price = $defaultVariant->compare_at_amount;
                $this->sku = $defaultVariant->sku ?? '';
            }
        } else {
            $this->authorize('create', Product::class);
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description_html' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
        ]);

        $productService = app(ProductService::class);
        $store = app('current_store');

        $tagsArray = $this->tags !== ''
            ? array_map('trim', explode(',', $this->tags))
            : [];

        if ($this->product) {
            $productService->update($this->product, [
                'title' => $this->title,
                'description_html' => $this->description_html,
                'vendor' => $this->vendor,
                'product_type' => $this->product_type,
                'tags' => $tagsArray,
            ]);

            $defaultVariant = $this->product->variants()->where('is_default', true)->first();
            if ($defaultVariant) {
                $defaultVariant->update([
                    'price_amount' => $this->price_amount,
                    'compare_at_amount' => $this->compare_at_price,
                    'sku' => $this->sku ?: null,
                ]);
            }

            if ($this->product->status->value !== $this->status) {
                try {
                    $productService->transitionStatus($this->product, ProductStatus::from($this->status));
                } catch (\Exception) {
                    // Status transition not allowed
                }
            }

            session()->flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);
        } else {
            $product = $productService->create($store, [
                'title' => $this->title,
                'description_html' => $this->description_html,
                'vendor' => $this->vendor,
                'product_type' => $this->product_type,
                'tags' => $tagsArray,
                'price_amount' => $this->price_amount,
                'sku' => $this->sku ?: null,
            ]);

            session()->flash('toast', ['type' => 'success', 'message' => __('Product created.')]);
            $this->redirect(route('admin.products.edit', $product), navigate: true);

            return;
        }

        $this->redirect(route('admin.products.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.products.form');
    }
}
