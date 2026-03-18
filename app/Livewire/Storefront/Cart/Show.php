<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\DiscountService;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cart')]
class Show extends Component
{
    public string $discountCode = '';

    public ?string $appliedCode = null;

    public ?string $discountDescription = null;

    public ?int $discountAmount = null;

    public ?string $discountError = null;

    public function applyDiscount(): void
    {
        $this->discountError = null;
        $this->appliedCode = null;
        $this->discountDescription = null;
        $this->discountAmount = null;

        $cart = $this->getCart();
        if (! $cart || ! $this->discountCode) {
            return;
        }

        $store = $cart->store ?? app('current_store');
        $discountService = app(DiscountService::class);

        try {
            $discount = $discountService->validate($this->discountCode, $store, $cart);

            $subtotal = $cart->lines->sum('line_total_amount');
            $lines = $cart->lines->map(fn ($l) => ['id' => $l->id, 'subtotal' => $l->line_subtotal_amount])->all();
            $result = $discountService->calculate($discount, $subtotal, $lines);

            $this->appliedCode = strtoupper($this->discountCode);
            $this->discountAmount = $result->amount;

            if ($result->isFreeShipping) {
                $this->discountDescription = 'Free shipping applied';
            } elseif ($discount->value_type->value === 'percent') {
                $this->discountDescription = "{$discount->value_amount}% off";
            } else {
                $this->discountDescription = number_format($discount->value_amount / 100, 2).' '.$cart->currency.' off';
            }
        } catch (InvalidDiscountException $e) {
            $this->discountError = match ($e->reason) {
                'not_found' => 'Discount code not found.',
                'expired' => 'This discount code has expired.',
                'not_yet_active' => 'This discount code is not yet active.',
                'usage_limit_reached' => 'This discount code has reached its usage limit.',
                'minimum_not_met' => 'Minimum purchase amount not met.',
                default => 'Invalid discount code.',
            };
        }
    }

    public function removeDiscount(): void
    {
        $this->appliedCode = null;
        $this->discountCode = '';
        $this->discountDescription = null;
        $this->discountAmount = null;
        $this->discountError = null;
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        if ($quantity <= 0) {
            $cartService->removeLine($cart, $lineId);
        } else {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        }

        $this->dispatch('cart-count-updated');
    }

    public function removeLine(int $lineId): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->dispatch('cart-count-updated');
    }

    public function proceedToCheckout(): mixed
    {
        return $this->redirect(route('storefront.checkout'));
    }

    public function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->with(['lines.variant.product', 'lines.variant.optionValues.option'])
            ->find($cartId);
    }

    public function render(): View
    {
        return view('livewire.storefront.cart.show', [
            'cart' => $this->getCart(),
        ])->layout('storefront.layouts.app', ['title' => 'Cart']);
    }
}
