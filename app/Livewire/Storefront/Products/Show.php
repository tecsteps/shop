<?php

namespace App\Livewire\Storefront\Products;

use App\Enums\AnalyticsEventType;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\AnalyticsService;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public ?Product $product = null;

    public ?int $selected_variant_id = null;

    public string $add_to_cart_error = '';

    public function mount(string $handle, AnalyticsService $analytics): void
    {
        $this->handle = $handle;

        $this->product = Product::query()
            ->with('variants')
            ->where('handle', $handle)
            ->first();

        if ($this->product !== null) {
            $firstVariant = $this->product->variants->first();
            $this->selected_variant_id = $firstVariant?->getKey();
        }

        $store = app()->bound('current_store') ? app('current_store') : null;

        if ($this->product !== null && $store instanceof Store) {
            $analytics->track(
                $store,
                AnalyticsEventType::ProductView,
                ['product_id' => $this->product->getKey(), 'handle' => $this->product->handle],
                session()->getId(),
            );
        }
    }

    public function addToCart(CartService $cartService): void
    {
        $this->add_to_cart_error = '';

        if ($this->selected_variant_id === null) {
            $this->add_to_cart_error = 'Please select a variant.';

            return;
        }

        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store instanceof Store) {
            $this->add_to_cart_error = 'Store not found.';

            return;
        }

        $variant = ProductVariant::query()->find($this->selected_variant_id);

        if ($variant === null || (int) $variant->product?->store_id !== (int) $store->getKey()) {
            $this->add_to_cart_error = 'Variant not available.';

            return;
        }

        try {
            $cart = $cartService->getOrCreateForSession($store);
            $cartService->addLine($cart, (int) $variant->getKey(), 1);
        } catch (\Throwable $e) {
            $this->add_to_cart_error = $e->getMessage();

            return;
        }

        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show');
    }
}
