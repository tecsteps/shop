<?php

namespace App\Livewire\Storefront\Checkout;

use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\AnalyticsService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\ShippingCalculator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Checkout')]
class Show extends Component
{
    public ?int $checkoutId = null;

    public int $step = 1;

    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $country = 'DE';

    public string $postalCode = '';

    public string $phone = '';

    public ?int $selectedRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardExpiry = '';

    public string $cardCvv = '';

    public string $discountCode = '';

    public ?string $appliedDiscountCode = null;

    public ?string $discountDescription = null;

    public ?string $discountError = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $cart = $this->getCart();
        if (! $cart || $cart->lines->isEmpty()) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        $checkoutService = app(CheckoutService::class);
        $checkout = $checkoutService->createFromCart($cart);
        $this->checkoutId = $checkout->id;

        $store = app('current_store');
        app(AnalyticsService::class)->track($store, 'checkout_started', [
            'checkout_id' => $checkout->id,
        ], session()->getId());
    }

    public function submitAddress(): void
    {
        $this->validate([
            'email' => 'required|email',
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'address1' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string|size:2',
            'postalCode' => 'required|string',
        ]);

        $checkout = Checkout::withoutGlobalScopes()->find($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->setAddress($checkout, [
            'email' => $this->email,
            'shipping_address' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'address1' => $this->address1,
                'address2' => $this->address2,
                'city' => $this->city,
                'country' => $this->country,
                'postal_code' => $this->postalCode,
                'phone' => $this->phone,
            ],
        ]);

        $this->step = 2;
    }

    public function applyDiscount(): void
    {
        $this->discountError = null;
        $this->appliedDiscountCode = null;
        $this->discountDescription = null;

        $checkout = Checkout::withoutGlobalScopes()->find($this->checkoutId);
        if (! $checkout || ! $this->discountCode) {
            return;
        }

        $store = $checkout->store;
        $cart = $checkout->cart()->with('lines')->first();
        $discountService = app(DiscountService::class);

        try {
            $discount = $discountService->validate($this->discountCode, $store, $cart);

            $this->appliedDiscountCode = strtoupper($this->discountCode);

            if ($discount->value_type->value === 'free_shipping') {
                $this->discountDescription = 'Free shipping';
            } elseif ($discount->value_type->value === 'percent') {
                $this->discountDescription = "{$discount->value_amount}% off";
            } else {
                $this->discountDescription = number_format($discount->value_amount / 100, 2).' '.($cart->currency ?? 'EUR').' off';
            }

            $checkout->update(['discount_code' => $this->appliedDiscountCode]);

            $checkoutService = app(CheckoutService::class);
            $checkoutService->recalculatePublic($checkout);
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
        $this->appliedDiscountCode = null;
        $this->discountCode = '';
        $this->discountDescription = null;
        $this->discountError = null;

        $checkout = Checkout::withoutGlobalScopes()->find($this->checkoutId);
        if ($checkout) {
            $checkout->update(['discount_code' => null]);
            $checkoutService = app(CheckoutService::class);
            $checkoutService->recalculatePublic($checkout);
        }
    }

    public function submitShipping(): void
    {
        $this->validate([
            'selectedRateId' => 'required|integer',
        ]);

        $checkout = Checkout::withoutGlobalScopes()->find($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->setShippingMethod($checkout, $this->selectedRateId);
        $this->step = 3;
    }

    public function submitPayment(): void
    {
        $this->errorMessage = null;

        $checkout = Checkout::withoutGlobalScopes()->find($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);

        $paymentData = [];
        if ($this->paymentMethod === 'credit_card') {
            $paymentData = ['card_number' => $this->cardNumber];
        }

        try {
            $order = $checkoutService->completeCheckout($checkout->fresh(), $paymentData);

            $store = app('current_store');
            app(AnalyticsService::class)->track($store, 'checkout_completed', [
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
            ], session()->getId());

            session()->forget('cart_id');
            session()->put('last_order_id', $order->id);
            $this->redirect(route('storefront.checkout.confirmation'));
        } catch (PaymentFailedException $e) {
            $this->errorMessage = match ($e->errorCode) {
                'card_declined' => 'Payment was declined. Please try a different card.',
                'insufficient_funds' => 'Insufficient funds. Please try a different card.',
                default => 'Payment failed. Please try again.',
            };
        }
    }

    public function getAvailableRates(): Collection
    {
        $checkout = Checkout::withoutGlobalScopes()->find($this->checkoutId);
        if (! $checkout || ! $checkout->shipping_address_json) {
            return collect();
        }

        $store = $checkout->store;
        $calculator = app(ShippingCalculator::class);

        return $calculator->getAvailableRates($store, $checkout->shipping_address_json);
    }

    private function getCart(): ?Cart
    {
        $cartId = session('cart_id');
        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->with(['lines.variant.product'])
            ->find($cartId);
    }

    public function render(): View
    {
        $checkout = $this->checkoutId
            ? Checkout::withoutGlobalScopes()->find($this->checkoutId)
            : null;

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'availableRates' => $this->step === 2 ? $this->getAvailableRates() : collect(),
        ])->layout('storefront.layouts.app', ['title' => 'Checkout']);
    }
}
