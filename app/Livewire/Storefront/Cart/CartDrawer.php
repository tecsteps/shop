<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\ManagesCart;
use App\Models\Cart;
use App\Services\CheckoutService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    use ManagesCart;

    public bool $open = false;

    #[Computed]
    public function cart(): ?Cart
    {
        return $this->currentCart();
    }

    #[Computed]
    public function itemCount(): int
    {
        return (int) ($this->cart?->lines->sum('quantity') ?? 0);
    }

    #[Computed]
    public function discountAmount(): int
    {
        return $this->discountPreview($this->cart);
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->cart, $this->itemCount, $this->discountAmount);
    }

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

    public function proceedToCheckout(): void
    {
        $cart = $this->cart;

        if (! $cart || $cart->lines->isEmpty()) {
            return;
        }

        $checkout = app(CheckoutService::class)->create($cart);

        if ($code = session('cart_discount_code')) {
            $checkout->update(['discount_code' => $code]);
        }

        $this->redirect(route('storefront.checkout.show', $checkout));
    }

    public function render()
    {
        return view('livewire.storefront.cart.cart-drawer');
    }
}
