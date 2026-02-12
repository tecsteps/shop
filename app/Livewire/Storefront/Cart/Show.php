<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Your Cart')]
class Show extends Component
{
    public string $discountCode = '';

    public string $discountError = '';

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        try {
            if ($quantity <= 0) {
                $cartService->removeLine($cart, $lineId);
            } else {
                $cartService->updateLineQuantity($cart, $lineId, $quantity);
            }
            $this->dispatchCartUpdated($cart);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->dispatchCartUpdated($cart);
    }

    public function applyDiscount(): void
    {
        $this->discountError = '';
        $cart = $this->getCart();
        if (! $cart || empty($this->discountCode)) {
            return;
        }

        try {
            $discountService = app(DiscountService::class);
            $discountService->validate($this->discountCode, $cart);
            $cart->update(['discount_code' => $this->discountCode]);
            $this->discountCode = '';
        } catch (InvalidDiscountException $e) {
            $this->discountError = $e->getMessage();
        }
    }

    public function removeDiscount(): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cart->update(['discount_code' => null]);
    }

    public function proceedToCheckout(): void
    {
        $cart = $this->getCart();
        if (! $cart || $cart->lines->isEmpty()) {
            return;
        }

        $store = app('current_store');
        $checkoutService = app(CheckoutService::class);
        $checkout = $checkoutService->createFromCart($cart, $store);

        $this->redirect(route('storefront.checkout.show', $checkout->id));
    }

    public function render()
    {
        $cart = $this->getCart();
        $lines = $cart ? $cart->lines()->with('variant.product.media')->get() : collect();
        $subtotal = $lines->sum('line_subtotal_amount');
        $discountAmount = 0;

        if ($cart?->discount_code) {
            try {
                $discountService = app(DiscountService::class);
                $discount = $discountService->validate($cart->discount_code, $cart);
                $discountAmount = $discountService->calculateDiscountAmount($discount, $cart);
            } catch (\Exception) {
                // Discount no longer valid
            }
        }

        $total = max(0, $subtotal - $discountAmount);

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
        ]);
    }

    private function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::with('lines')->find($cartId);
    }

    private function dispatchCartUpdated(Cart $cart): void
    {
        $itemCount = $cart->lines()->sum('quantity');
        $this->dispatch('cart-updated', cartId: $cart->id, itemCount: $itemCount);
    }
}
