<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\InteractsWithCart;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Full cart page at GET /cart (spec 04 §7): same features as the drawer
 * plus an order summary with a shipping estimate note.
 */
class Show extends Component
{
    use InteractsWithCart;

    /**
     * Proceed to checkout (step 1 collects contact and address).
     */
    public function checkout()
    {
        return $this->redirectRoute('storefront.checkout.show', ['checkoutId' => 'new']);
    }

    /**
     * Render the cart page.
     */
    public function render(): View
    {
        $cart = $this->sessionCart();
        $cart?->loadMissing(['lines.variant.product.media', 'lines.variant.optionValues.option']);

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'discount' => $cart !== null ? $this->appliedSessionDiscount($cart) : null,
        ])
            ->layout('storefront.layouts.app')
            ->title('Your Cart - '.app('current_store')->name);
    }
}
