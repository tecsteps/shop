<?php

namespace App\Livewire\Storefront\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle;

    public ?int $selectedVariantId = null;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function addToCart(): void
    {
        if ($this->selectedVariantId === null || $this->quantity < 1) {
            return;
        }

        $this->dispatch('cart:add-line', variantId: $this->selectedVariantId, quantity: $this->quantity);
    }

    public function render()
    {
        if (! Schema::hasTable('products') || ! app()->bound('current_store')) {
            throw new NotFoundHttpException('Products not available');
        }

        $storeId = app('current_store')->id;

        $product = DB::table('products')
            ->where('store_id', $storeId)
            ->where('handle', $this->handle)
            ->where('status', 'active')
            ->first(['id', 'title', 'handle', 'description_html', 'tags']);

        if (! $product) {
            throw new NotFoundHttpException('Product not found');
        }

        $variants = Schema::hasTable('product_variants')
            ? DB::table('product_variants')
                ->where('product_id', $product->id)
                ->orderBy('position')
                ->get(['id', 'sku', 'price_amount', 'compare_at_amount', 'currency', 'is_default', 'status'])
                ->all()
            : [];

        $media = Schema::hasTable('product_media')
            ? DB::table('product_media')
                ->where('product_id', $product->id)
                ->orderBy('position')
                ->get(['id', 'storage_key', 'alt_text'])
                ->all()
            : [];

        if ($this->selectedVariantId === null && ! empty($variants)) {
            $default = collect($variants)->firstWhere('is_default', 1) ?? $variants[0];
            $this->selectedVariantId = (int) $default->id;
        }

        return view('livewire.storefront.products.show', [
            'product' => $product,
            'variants' => $variants,
            'media' => $media,
        ]);
    }
}
