<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InvalidCartMutationException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $email = '';

    public string $discountCode = '';

    public function mount(): void
    {
        $this->syncDiscountCode();
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $quantity, $cart->cart_version);
            $this->syncDiscountCode();
            $this->dispatch('cart-updated');
        } catch (InvalidCartMutationException $exception) {
            $this->addError('cart', $exception->getMessage());
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId, $cart->cart_version);
        $this->syncDiscountCode();
        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $this->discountCode = trim($this->discountCode);

        $this->validate([
            'discountCode' => ['required', 'string', 'max:50'],
        ]);

        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return;
        }

        try {
            $cart = app(CartService::class)->applyDiscount($cart, $this->discountCode);
            $this->discountCode = $cart->discount_code ?? '';
            $this->resetErrorBag('cart');
            $this->resetErrorBag('discountCode');
            $this->dispatch('cart-updated');
        } catch (InvalidCartMutationException|InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(): void
    {
        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return;
        }

        try {
            app(CartService::class)->removeDiscount($cart);
            $this->discountCode = '';
            $this->resetErrorBag('cart');
            $this->resetErrorBag('discountCode');
            $this->dispatch('cart-updated');
        } catch (InvalidCartMutationException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function startCheckout(): mixed
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $cart = $this->cart();

        if (! $cart instanceof Cart || $cart->lines->isEmpty()) {
            $this->addError('cart', 'Add at least one item before checkout.');

            return null;
        }

        $checkout = app(CheckoutService::class)->createFromCart($cart, $this->email);

        return $this->redirect(route('storefront.checkout.show', $checkout), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.storefront.cart.show', [
            'cart' => $this->cart(),
        ])
            ->layout('storefront.layouts.app', [
                'title' => 'Cart',
            ]);
    }

    private function cart(): ?Cart
    {
        $cartId = session(CartService::SESSION_KEY);

        if (! $cartId) {
            return null;
        }

        $cart = Cart::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->whereKey($cartId)
            ->first();

        return $cart instanceof Cart ? app(CartService::class)->loadForDisplay($cart) : null;
    }

    private function syncDiscountCode(): void
    {
        $this->discountCode = $this->cart()?->discount_code ?? '';
    }
}
