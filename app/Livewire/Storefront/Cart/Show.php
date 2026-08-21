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

    public int $discountAmount = 0;

    public int $totalAmount = 0;

    public function mount(CartService $carts, DiscountService $discounts): void
    {
        $this->cart = $carts->getOrCreateForSession(app('current_store'), auth('customer')->user());
        $this->discountCode = (string) ($this->cart->discount_code ?? '');
        $this->refreshCart($discounts);
    }

    public function increase(int $lineId, CartService $carts, DiscountService $discounts): void
    {
        $line = $this->cart->lines->firstWhere('id', $lineId);

        if ($line === null) {
            return;
        }

        $carts->updateLineQuantity($this->cart, $lineId, $line->quantity + 1);
        $this->refreshCart($discounts);
    }

    public function decrease(int $lineId, CartService $carts, DiscountService $discounts): void
    {
        $line = $this->cart->lines->firstWhere('id', $lineId);

        if ($line === null) {
            return;
        }

        if ($line->quantity > 1) {
            $carts->updateLineQuantity($this->cart, $lineId, $line->quantity - 1);
        }

        $this->refreshCart($discounts);
    }

    public function remove(int $lineId, CartService $carts, DiscountService $discounts): void
    {
        if (! $this->cart->lines->contains('id', $lineId)) {
            return;
        }

        $carts->removeLine($this->cart, $lineId);
        $this->message = 'Item removed from your cart.';
        $this->refreshCart($discounts);
    }

    public function checkout(CheckoutService $checkouts): void
    {
        if ($this->cart->lines->isEmpty()) {
            $this->addError('cart', 'Your cart is empty.');

            return;
        }

        $checkout = $checkouts->create($this->cart, auth('customer')->user()?->email ?? 'guest@example.com', auth('customer')->user());
        $this->redirect(route('checkout.show', $checkout), navigate: true);
    }

    public function applyDiscount(DiscountService $discounts): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);

        try {
            $discount = $discounts->validate(trim($this->discountCode), app('current_store'), $this->cart);
            $this->cart->update(['discount_code' => $discount->code]);
            $this->discountCode = $discount->code;
            $this->message = 'Discount applied.';
            $this->resetValidation('discountCode');
            $this->refreshCart($discounts);
        } catch (InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(DiscountService $discounts): void
    {
        $this->cart->update(['discount_code' => null]);
        $this->discountCode = '';
        $this->message = 'Discount removed.';
        $this->resetValidation('discountCode');
        $this->refreshCart($discounts);
    }

    public function formatMoney(int|float $amount): string
    {
        return number_format((float) $amount / 100, 2, '.', ',').' '.($this->cart->currency ?: 'EUR');
    }

    private function refreshCart(DiscountService $discounts): void
    {
        $this->cart = $this->cart->refresh()->load(['lines.variant.product.media', 'lines.variant.product.collections', 'lines.variant.inventory']);
        $this->discountAmount = $this->calculateDiscountAmount($discounts);
        $subtotal = (int) $this->cart->lines->sum('line_subtotal_amount');
        $this->totalAmount = max(0, $subtotal - $this->discountAmount);
    }

    private function calculateDiscountAmount(DiscountService $discounts): int
    {
        if ($this->cart->discount_code === null || $this->cart->lines->isEmpty()) {
            return 0;
        }

        try {
            $discount = $discounts->validate($this->cart->discount_code, app('current_store'), $this->cart);
        } catch (InvalidDiscountException) {
            return 0;
        }

        $subtotal = (int) $this->cart->lines->sum('line_subtotal_amount');
        $result = $discounts->calculate($discount, $subtotal, $this->cart->lines->map(fn ($line): array => [
            'line_id' => $line->id,
            'amount' => $line->line_subtotal_amount,
            'product_id' => $line->variant->product_id,
            'collection_ids' => $line->variant->product->collections->modelKeys(),
        ])->all());

        return $result->amount;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.cart.show')->layout('layouts.storefront');
    }
}
