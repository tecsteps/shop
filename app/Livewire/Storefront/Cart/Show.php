<?php

namespace App\Livewire\Storefront\Cart;

use App\Exceptions\InvalidDiscountException;
use App\Services\CartService;
use App\Services\DiscountService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $discountCode = '';

    public ?string $discountError = null;

    /** @var array<string, string> */
    protected $listeners = [
        'cart-updated' => '$refresh',
    ];

    public function updateQuantity(int $lineId, int $quantity): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cartService->updateLineQuantity($cart, $lineId, $quantity);
    }

    public function removeLine(int $lineId): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cartService->removeLine($cart, $lineId);
    }

    public function applyDiscount(): void
    {
        $this->discountError = null;

        if (trim($this->discountCode) === '') {
            $this->discountError = 'Please enter a discount code.';

            return;
        }

        $store = app('current_store');
        $cartService = app(CartService::class);
        $discountService = app(DiscountService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cart->load('lines');

        try {
            $discountService->validate($this->discountCode, $store, $cart);
            session()->put('discount_code', trim($this->discountCode));
        } catch (InvalidDiscountException $e) {
            $this->discountError = match ($e->reason) {
                'not_found' => 'This discount code is not valid.',
                'disabled' => 'This discount code is no longer active.',
                'not_yet_active' => 'This discount code is not yet active.',
                'expired' => 'This discount code has expired.',
                'usage_limit_reached' => 'This discount code has reached its usage limit.',
                'minimum_not_met' => 'Your cart does not meet the minimum purchase requirement.',
                default => 'This discount code could not be applied.',
            };
        }
    }

    public function removeDiscount(): void
    {
        session()->forget('discount_code');
        $this->discountCode = '';
        $this->discountError = null;
    }

    public function render(): mixed
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);
        $cart->load('lines.variant.product', 'lines.variant.optionValues');

        $appliedDiscount = null;
        $discountResult = null;
        $appliedCode = session('discount_code');

        if ($appliedCode && $cart->lines->isNotEmpty()) {
            $discountService = app(DiscountService::class);

            try {
                $appliedDiscount = $discountService->validate($appliedCode, $store, $cart);
                $subtotal = $cart->lines->sum('total');
                $discountResult = $discountService->calculate($appliedDiscount, $subtotal, $cart->lines->all());
            } catch (InvalidDiscountException) {
                session()->forget('discount_code');
                $appliedDiscount = null;
            }
        }

        return view('livewire.storefront.cart.show', [
            'cart' => $cart,
            'appliedDiscount' => $appliedDiscount,
            'discountResult' => $discountResult,
            'appliedCode' => $appliedCode,
        ]);
    }
}
