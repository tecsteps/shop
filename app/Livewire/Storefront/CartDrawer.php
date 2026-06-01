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
 * whenever a `cart-updated` event is broadcast. All mutations go through the
 * service so cart_version, inventory checks, and line recalculation stay
 * centralized.
 *
 * Event contract (shared with the storefront layout, owned by the storefront
 * teammate): the header opens the drawer with the window event `open-cart-drawer`
 * and reads the cart badge from `cart-updated`'s `detail.itemCount`. Every
 * mutation here therefore re-dispatches `cart-updated` with
 * `{ itemCount, cartId }`. Storefront owns final visual polish (task #6).
 */
class CartDrawer extends Component
{
    public bool $open = false;

    /**
     * Open the drawer in response to the header's browser event.
     */
    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    /**
     * Close the drawer (also reachable via the `close-cart-drawer` event).
     */
    #[On('close-cart-drawer')]
    public function closeDrawer(): void
    {
        $this->open = false;
    }

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
        $this->announceUpdate();
    }

    public function increment(int $lineId): void
    {
        $cart = $this->cart();
        $line = $cart->lines()->find($lineId);

        if ($line !== null) {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $line->quantity + 1);
            $this->announceUpdate();
        }
    }

    public function decrement(int $lineId): void
    {
        $cart = $this->cart();
        $line = $cart->lines()->find($lineId);

        if ($line !== null) {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $line->quantity - 1);
            $this->announceUpdate();
        }
    }

    public function remove(int $lineId): void
    {
        app(CartService::class)->removeLine($this->cart(), $lineId);
        $this->announceUpdate();
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
     * Re-dispatch `cart-updated` with the item count + cart id the header badge
     * listens for.
     */
    private function announceUpdate(): void
    {
        $cart = $this->cart();

        $this->dispatch('cart-updated', itemCount: (int) $cart->lines()->sum('quantity'), cartId: $cart->id);
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
