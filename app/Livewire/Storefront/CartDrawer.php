<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithCart;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    use InteractsWithCart;

    public bool $open = false;

    #[On('open-cart')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    #[On('cart-updated')]
    public function refreshAndOpen(): void
    {
        $this->open = true;
    }

    public function closeDrawer(): void
    {
        $this->open = false;
    }

    public function render(): View
    {
        $cart = $this->currentCart();
        $lines = $this->cartLineData($cart);
        $discount = $this->appliedDiscount($cart);
        $subtotal = $cart?->subtotalAmount() ?? 0;

        return view('livewire.storefront.cart-drawer', [
            'lines' => $lines,
            'itemCount' => $cart?->itemCount() ?? 0,
            'currency' => $cart?->currency ?? $this->currentStore()->default_currency,
            'subtotalAmount' => $subtotal,
            'discount' => $discount,
            'estimatedTotal' => max(0, $subtotal - ($discount['amount'] ?? 0)),
        ]);
    }
}
