<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use Livewire\Component;

class Show extends Component
{
    public Cart $cart;

    public string $discountCode = '';

    public string $message = '';

    public function mount(CartService $carts): void
    {
        $this->cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
    }

    public function increase(int $lineId, CartService $carts): void
    {
        $line = $this->cart->lines->firstWhere('id', $lineId);
        $carts->updateLineQuantity($this->cart, $lineId, $line->quantity + 1);
        $this->refreshCart();
    }

    public function decrease(int $lineId, CartService $carts): void
    {
        $line = $this->cart->lines->firstWhere('id', $lineId);

        if ($line->quantity > 1) {
            $carts->updateLineQuantity($this->cart, $lineId, $line->quantity - 1);
        }

        $this->refreshCart();
    }

    public function remove(int $lineId, CartService $carts): void
    {
        $carts->removeLine($this->cart, $lineId);
        $this->refreshCart();
    }

    public function checkout(CheckoutService $checkouts): void
    {
        $checkout = $checkouts->create($this->cart, auth('customer')->user()?->email ?? 'guest@example.com', auth('customer')->user());
        $this->redirect(route('checkout.show', $checkout), navigate: true);
    }

    public function applyDiscount(DiscountService $discounts): void
    {
        try {
            $discount = $discounts->validate($this->discountCode, app('current_store'), $this->cart);
            $this->cart->update(['discount_code' => $discount->code]);
            $this->message = 'Discount applied';
        } catch (InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    private function refreshCart(): void
    {
        $this->cart = $this->cart->refresh()->load(['lines.variant.product', 'lines.variant.inventory']);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart.show')->layout('layouts.storefront');
    }
}
