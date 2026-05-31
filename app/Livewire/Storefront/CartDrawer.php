<?php

namespace App\Livewire\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Slide-out cart drawer: line items, quantity controls, and a checkout link.
 *
 * Resolves the active session cart through {@see CartService} and refreshes
 * whenever a `cart-updated` event is broadcast (for example from a product
 * page's add-to-cart). All mutations go through the service so cart_version,
 * inventory checks, and line recalculation stay centralized; an
 * `add-to-cart`/`cart-updated` round-trip keeps every cart surface in sync.
 *
 * Storefront teammate owns final visual polish (task #6); this provides the
 * functional, testable backing component and a minimal view.
 */
class CartDrawer extends Component
{
    public bool $open = false;

    /**
     * Add a variant to the cart and announce the change.
     */
    #[On('add-to-cart')]
    public function addToCart(int $variantId, int $quantity = 1): void
    {
        try {
            app(CartService::class)->addLine($this->cart(), $variantId, $quantity);
        } catch (InsufficientInventoryException) {
            $this->dispatch('cart-error', message: __('Not enough stock available.'));

            return;
        }

        $this->open = true;
        $this->dispatch('cart-updated');
    }

    public function increment(int $lineId): void
    {
        $cart = $this->cart();
        $line = $cart->lines()->find($lineId);

        if ($line !== null) {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $line->quantity + 1);
            $this->dispatch('cart-updated');
        }
    }

    public function decrement(int $lineId): void
    {
        $cart = $this->cart();
        $line = $cart->lines()->find($lineId);

        if ($line !== null) {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $line->quantity - 1);
            $this->dispatch('cart-updated');
        }
    }

    public function remove(int $lineId): void
    {
        app(CartService::class)->removeLine($this->cart(), $lineId);
        $this->dispatch('cart-updated');
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        // The render pass below reloads the cart; this handler exists so the
        // drawer reacts to cart-updated events fired by sibling components.
    }

    public function render()
    {
        $cart = $this->cart()->load('lines.variant.product');

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'lines' => $cart->lines,
            'subtotal' => $cart->subtotalAmount(),
        ]);
    }

    /**
     * The current session cart for the active store and customer.
     */
    private function cart(): Cart
    {
        return app(CartService::class)->getOrCreateForSession(
            app('current_store'),
            Auth::guard('customer')->user(),
        );
    }
}
