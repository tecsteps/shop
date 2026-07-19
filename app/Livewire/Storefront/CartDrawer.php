<?php

namespace App\Livewire\Storefront;

use App\Exceptions\InsufficientInventoryException;
use App\Livewire\Storefront\Concerns\InteractsWithCart;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Slide-out cart drawer content (spec 04 §6). The drawer shell lives in the
 * storefront layout; this component renders lines, quantity controls, the
 * discount input and totals inside it.
 */
class CartDrawer extends Component
{
    use InteractsWithCart;

    /**
     * Add a variant from any add-to-cart dispatch, then open the drawer.
     */
    #[On('add-to-cart')]
    public function addToCart(int $variantId, int $quantity = 1): void
    {
        $carts = app(CartService::class);
        $cart = $carts->getOrCreateForSession($this->currentStore(), auth('customer')->user());

        try {
            $carts->addLine($cart, $variantId, $quantity);
        } catch (InsufficientInventoryException|ValidationException) {
            return;
        }

        $this->broadcastCartCount($cart->refresh());
        $this->dispatch('cart-drawer-open');
    }

    /**
     * Re-render when another component changed the cart.
     */
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        // The render cycle reloads the session cart.
    }

    /**
     * Proceed to checkout (step 1 collects contact and address).
     */
    public function checkout()
    {
        return $this->redirectRoute('storefront.checkout.show', ['checkoutId' => 'new']);
    }

    /**
     * Render the drawer content.
     */
    public function render(): View
    {
        $cart = $this->sessionCart();
        $cart?->loadMissing(['lines.variant.product.media', 'lines.variant.optionValues.option']);

        return view('livewire.storefront.cart-drawer', [
            'cart' => $cart,
            'discount' => $cart !== null ? $this->appliedSessionDiscount($cart) : null,
        ]);
    }
}
