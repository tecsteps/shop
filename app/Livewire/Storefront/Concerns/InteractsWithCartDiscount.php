<?php

namespace App\Livewire\Storefront\Concerns;

use App\Enums\DiscountValueType;
use App\Exceptions\InvalidDiscountException;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use Livewire\Attributes\Computed;
use InvalidArgumentException;

/**
 * Cart-level discount code handling shared by the cart drawer and the full
 * cart page. The validated code is kept in the session and carried over to
 * the checkout when it is created.
 */
trait InteractsWithCartDiscount
{
    public string $discountCode = '';

    public ?string $discountError = null;

    public function applyCartDiscount(): void
    {
        $this->discountError = null;

        $code = trim($this->discountCode);

        if ($code === '') {
            $this->discountError = 'Please enter a discount code.';

            return;
        }

        try {
            app(DiscountService::class)->validate($code, $this->store(), $this->sessionCart());
        } catch (InvalidDiscountException $e) {
            $this->discountError = $e->getMessage();

            return;
        }

        session(['cart_discount_code' => $code]);
        $this->discountCode = '';
    }

    public function removeCartDiscount(): void
    {
        session()->forget('cart_discount_code');
    }

    /**
     * @return array{code: string, label: string, amount: int}|null
     */
    #[Computed]
    public function cartDiscount(): ?array
    {
        $code = session('cart_discount_code');

        if (! $code) {
            return null;
        }

        $cart = $this->sessionCart()->loadMissing('lines');

        try {
            $discount = app(DiscountService::class)->validate($code, $this->store(), $cart);
        } catch (InvalidDiscountException) {
            return null;
        }

        $subtotal = $cart->lines->sum(fn ($line) => $line->unit_price_amount * $line->quantity);

        $lineData = $cart->lines->map(fn ($line) => [
            'id' => $line->id,
            'subtotal' => $line->unit_price_amount * $line->quantity,
            'product_id' => $line->variant?->product_id,
        ])->all();

        $result = app(DiscountService::class)->calculate($discount, $subtotal, $lineData);

        return [
            'code' => $code,
            'label' => $this->discountLabel($discount, $result->amount),
            'amount' => $result->amount,
        ];
    }

    /**
     * Create a checkout from the session cart and redirect to it.
     */
    public function checkout(): void
    {
        $cart = $this->sessionCart();

        if ($cart->lines()->count() === 0) {
            return;
        }

        $email = $this->customer()?->email ?? '';

        try {
            $checkout = app(CheckoutService::class)->create($cart, $email);
        } catch (InvalidArgumentException) {
            return;
        }

        $this->applySessionDiscountToCheckout($checkout);

        $this->redirect(route('storefront.checkout', ['checkoutId' => $checkout->id]));
    }

    protected function applySessionDiscountToCheckout(Checkout $checkout): Checkout
    {
        $code = session('cart_discount_code');

        if ($code) {
            try {
                app(DiscountService::class)->validate($code, $this->store(), $checkout->cart);
            } catch (InvalidDiscountException) {
                session()->forget('cart_discount_code');

                return $checkout;
            }

            $checkout->update(['discount_code' => $code]);
            $checkout->update(['totals_json' => app(PricingEngine::class)->calculate($checkout)->toArray()]);
            session()->forget('cart_discount_code');
        }

        return $checkout->fresh();
    }

    private function discountLabel(mixed $discount, int $amount): string
    {
        $valueType = $discount->value_type;

        return match ($valueType) {
            DiscountValueType::Percent->value => sprintf('(%s%% off)', $discount->value_amount),
            DiscountValueType::FreeShipping->value => '(Free shipping)',
            default => sprintf('(-%s)', $this->money($amount)),
        };
    }
}
