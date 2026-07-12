<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

trait QuickAddsProducts
{
    public function addToCart(int $variantId): void
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('status', 'active')
            ->whereHas('product', fn ($query) => $query
                ->where('store_id', $this->currentStore()->getKey())
                ->where('status', 'active')
                ->whereNotNull('published_at'))
            ->firstOrFail();

        try {
            $service = app(CartService::class);
            $cart = $service->getOrCreateForSession($this->currentStore(), Auth::guard('customer')->user());
            $service->addLine($cart, $variant->getKey(), 1);
            $cart->load('lines');
            $this->dispatch('cart-updated', cartId: $cart->getKey(), itemCount: (int) $cart->lines->sum('quantity'));
            $this->dispatch('toast', type: 'success', message: 'Added to cart');
        } catch (\Throwable $exception) {
            report($exception);
            $this->dispatch('toast', type: 'error', message: str_contains(strtolower($exception->getMessage()), 'stock')
                ? 'This product is currently out of stock'
                : 'The item could not be added.');
        }
    }
}
