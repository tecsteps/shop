<?php

namespace App\Livewire\Storefront;

use App\Models\Cart;
use App\Services\CartService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    public string $discountCode = '';

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    #[On('close-cart-drawer')]
    public function closeDrawer(): void
    {
        $this->open = false;
    }

    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        $this->open = true;
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        if ($quantity <= 0) {
            $cartService->removeLine($cart, $lineId);
        } else {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();

        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
    }

    public function render(): View
    {
        $cart = $this->getCart();
        $lines = $cart ? $cart->lines()->with('variant.product')->get() : collect();
        $subtotal = $lines->sum('line_total_amount');

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'itemCount' => $lines->sum('quantity'),
        ]);
    }

    private function getCart(): ?Cart
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return null;
        }

        $customer = auth('customer')->user();

        if ($customer) {
            return Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->first();
        }

        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->where('id', $cartId)
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->first();
    }
}
