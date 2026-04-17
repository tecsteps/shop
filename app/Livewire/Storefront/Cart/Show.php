<?php

namespace App\Livewire\Storefront\Cart;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\DiscountService;
use App\Services\ShippingCalculator;
use Livewire\Component;

class Show extends Component
{
    public string $discountCode = '';

    public string $discountError = '';

    public string $discountSuccess = '';

    public string $shippingCountry = '';

    /** @var array<int, array{id: int, name: string, price: int|null}> */
    public array $shippingRates = [];

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);

        try {
            $cartService->updateLineQuantity($cart, $lineId, $quantity);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->dispatch('cart-updated');
    }

    public function removeItem(int $lineId): void
    {
        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $cartService = app(CartService::class);
        $cartService->removeLine($cart, $lineId);

        $this->dispatch('cart-updated');
    }

    public function applyDiscount(): void
    {
        $this->discountError = '';
        $this->discountSuccess = '';

        $cart = $this->getCart();
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $cart || ! $store || empty(trim($this->discountCode))) {
            return;
        }

        try {
            $discountService = app(DiscountService::class);
            $discountService->validate($this->discountCode, $store, $cart);
            $this->discountSuccess = 'Discount code applied.';
        } catch (\App\Exceptions\InvalidDiscountException $e) {
            $this->discountError = match ($e->reasonCode) {
                'discount_not_found' => 'Invalid discount code.',
                'discount_expired' => 'This discount has expired.',
                'discount_not_yet_active' => 'This discount is not yet active.',
                'discount_usage_limit_reached' => 'This discount has reached its usage limit.',
                'discount_min_purchase_not_met' => 'Minimum purchase amount not met.',
                'discount_not_applicable' => 'This discount does not apply to your cart items.',
                default => 'Invalid discount code.',
            };
        }
    }

    public function updatedShippingCountry(): void
    {
        $this->shippingRates = [];

        if (empty($this->shippingCountry)) {
            return;
        }

        $store = app()->bound('current_store') ? app('current_store') : null;
        if (! $store) {
            return;
        }

        $cart = $this->getCart();
        if (! $cart) {
            return;
        }

        $calculator = app(ShippingCalculator::class);
        $rates = $calculator->getAvailableRates($store, ['country' => $this->shippingCountry]);

        $this->shippingRates = $rates->map(fn ($rate) => [
            'id' => $rate->id,
            'name' => $rate->name,
            'price' => $calculator->calculate($rate, $cart),
        ])->toArray();
    }

    public function proceedToCheckout(): mixed
    {
        $cart = $this->getCart();
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $cart || ! $store || $cart->lines->isEmpty()) {
            return null;
        }

        $checkout = Checkout::create([
            'store_id' => $store->id,
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'status' => CheckoutStatus::Started,
            'discount_code' => $this->discountSuccess ? $this->discountCode : null,
        ]);

        return $this->redirect(route('storefront.checkout', $checkout->id), navigate: true);
    }

    public function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::where('id', $cartId)
            ->where('status', CartStatus::Active)
            ->with(['lines.variant.product.media' => fn ($q) => $q->orderBy('position')->limit(1)])
            ->first();
    }

    public function render(): mixed
    {
        $cart = $this->getCart();
        $lines = $cart?->lines ?? collect();
        $subtotal = $lines->sum('line_total_amount');
        $itemCount = $lines->sum('quantity');
        $currency = $cart?->currency ?? 'EUR';

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'itemCount' => $itemCount,
            'currency' => $currency,
        ])->layout('layouts.storefront', ['title' => 'Cart']);
    }
}
