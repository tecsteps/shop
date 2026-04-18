<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $discountCode = '';

    public ?string $appliedDiscountCode = null;

    public ?string $discountError = null;

    public function mount(): void
    {
        $cart = $this->loadCart();
        $this->appliedDiscountCode = $cart?->getAttribute('applied_discount_code');
    }

    public function increment(int $lineId): void
    {
        $this->adjust($lineId, +1);
    }

    public function decrement(int $lineId): void
    {
        $this->adjust($lineId, -1);
    }

    public function remove(int $lineId): void
    {
        $cart = $this->loadCart();
        if (! $cart) {
            return;
        }

        app(CartService::class)->removeLine($cart, $lineId);
        $this->dispatch('cart:updated');
    }

    public function applyDiscount(): void
    {
        $cart = $this->loadCart();
        $this->discountError = null;

        if (! $cart || $this->discountCode === '') {
            return;
        }

        try {
            app(DiscountService::class)->validate($this->discountCode, app('current_store'), $cart);
            $this->appliedDiscountCode = strtoupper($this->discountCode);
            $this->discountCode = '';
        } catch (InvalidDiscountException $e) {
            $this->discountError = $e->getMessage();
            $this->appliedDiscountCode = null;
        }
    }

    public function removeDiscount(): void
    {
        $this->appliedDiscountCode = null;
    }

    public function checkout()
    {
        $cart = $this->loadCart();

        if (! $cart || $cart->lines->isEmpty()) {
            return;
        }

        $checkout = app(CheckoutService::class)->startFromCart($cart);
        if ($this->appliedDiscountCode) {
            $checkout->discount_code = $this->appliedDiscountCode;
            $checkout->save();
        }

        session()->put('checkout_id', $checkout->id);

        return redirect()->route('storefront.checkout.show');
    }

    public function render()
    {
        $cart = $this->loadCart();
        $pricing = null;
        $lines = [];

        if ($cart && $cart->lines->isNotEmpty()) {
            $pricing = app(PricingEngine::class)->calculateForCart(
                $cart,
                app('current_store'),
                discountCode: $this->appliedDiscountCode,
            );
            $cart->refresh();
            $cart->load('lines.variant.product.media');

            $lines = $cart->lines->map(fn ($line): array => [
                'id' => $line->id,
                'title' => $line->variant?->product?->title ?? 'Item',
                'sku' => $line->variant?->sku,
                'quantity' => $line->quantity,
                'unit_price_amount' => $line->unit_price_amount,
                'line_total_amount' => $line->line_total_amount,
            ])->all();
        }

        return view('livewire.storefront.cart.show', [
            'lines' => $lines,
            'totals' => $pricing?->toArray() ?? [
                'subtotal' => 0,
                'discount' => 0,
                'total' => 0,
                'currency' => $cart?->currency ?? 'USD',
            ],
            'currency' => $cart?->currency ?? 'USD',
            'isEmpty' => ! $cart || $cart->lines->isEmpty(),
        ]);
    }

    protected function adjust(int $lineId, int $delta): void
    {
        $cart = $this->loadCart();
        if (! $cart) {
            return;
        }

        $line = $cart->lines->firstWhere('id', $lineId);
        if (! $line) {
            return;
        }

        $new = $line->quantity + $delta;
        if ($new <= 0) {
            app(CartService::class)->removeLine($cart, $lineId);

            return;
        }

        try {
            app(CartService::class)->updateLineQuantity($cart, $lineId, $new);
        } catch (InsufficientInventoryException $e) {
            $this->dispatch('cart:error', message: $e->getMessage());
        }

        $this->dispatch('cart:updated');
    }

    protected function loadCart(): ?Cart
    {
        $cartId = session(CartService::SESSION_KEY);
        if (! $cartId) {
            return null;
        }

        return Cart::query()->with('lines.variant.product.media')->find($cartId);
    }
}
