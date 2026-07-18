<?php

namespace App\Livewire\Storefront\Cart;

use App\Livewire\Storefront\Concerns\ManagesCart;
use App\Models\Cart;
use App\Services\CheckoutService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Your Cart')]
class Show extends Component
{
    use ManagesCart;

    #[Computed]
    public function cart(): ?Cart
    {
        return $this->currentCart();
    }

    #[Computed]
    public function discountAmount(): int
    {
        return $this->discountPreview($this->cart);
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
        return view('livewire.storefront.cart.show');
    }
}
