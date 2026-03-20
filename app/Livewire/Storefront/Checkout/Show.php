<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Livewire\Component;

class Show extends Component
{
    public ?int $checkoutId = null;

    public string $step = 'contact';

    // Contact/Address fields
    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $country = 'DE';

    public string $postalCode = '';

    public string $provinceCode = '';

    // Shipping
    public ?int $selectedShippingRateId = null;

    // Payment
    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    // Discount
    public string $discountCode = '';

    public ?string $discountError = null;

    public ?string $error = null;

    public function mount(): void
    {
        $cart = $this->getCart();

        if (! $cart || $cart->lines->isEmpty()) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        $store = app('current_store');
        $checkoutService = app(CheckoutService::class);

        $checkout = Checkout::withoutGlobalScopes()
            ->where('cart_id', $cart->id)
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->first();

        if (! $checkout) {
            $checkout = $checkoutService->createFromCart($store, $cart);
        }

        $this->checkoutId = $checkout->id;
        $this->step = $this->stepFromStatus($checkout->status);

        if ($checkout->email) {
            $this->email = $checkout->email;
        }

        if ($checkout->shipping_address_json) {
            $addr = $checkout->shipping_address_json;
            $this->firstName = $addr['first_name'] ?? '';
            $this->lastName = $addr['last_name'] ?? '';
            $this->address1 = $addr['address1'] ?? '';
            $this->address2 = $addr['address2'] ?? '';
            $this->city = $addr['city'] ?? '';
            $this->country = $addr['country'] ?? 'DE';
            $this->postalCode = $addr['postal_code'] ?? '';
            $this->provinceCode = $addr['province_code'] ?? '';
        }
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
            'postalCode' => ['required', 'string', 'regex:/^[a-zA-Z0-9\s\-]{3,10}$/'],
        ]);

        $this->error = null;

        try {
            $checkoutService = app(CheckoutService::class);
            $checkout = $this->getCheckout();

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
                    'province_code' => $this->provinceCode,
                ],
            ]);

            $this->step = 'shipping';
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function submitShipping(): void
    {
        $this->error = null;

        try {
            $checkoutService = app(CheckoutService::class);
            $checkout = $this->getCheckout();

            $checkoutService->setShippingMethod($checkout, $this->selectedShippingRateId);

            $this->step = 'payment';
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function submitPayment(): void
    {
        $this->error = null;

        try {
            $checkoutService = app(CheckoutService::class);
            $checkout = $this->getCheckout();

            $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);

            $paymentMethodData = [];
            if ($this->paymentMethod === 'credit_card') {
                $paymentMethodData['card_number'] = $this->cardNumber;
            }

            $checkoutService->completeCheckout($checkout->fresh(), $paymentMethodData);

            session()->forget('cart_id');

            $this->redirect(route('storefront.checkout.confirmation', $checkout));
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function applyDiscount(): void
    {
        $this->discountError = null;

        try {
            $checkoutService = app(CheckoutService::class);
            $checkout = $this->getCheckout();
            $checkoutService->applyDiscount($checkout, $this->discountCode);
        } catch (\App\Exceptions\InvalidDiscountException $e) {
            $this->discountError = $e->getMessage();
        }
    }

    public function removeDiscount(): void
    {
        $checkoutService = app(CheckoutService::class);
        $checkout = $this->getCheckout();
        $checkoutService->removeDiscount($checkout);
        $this->discountCode = '';
    }

    private function getCart(): ?Cart
    {
        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->with('lines.variant.product')
            ->find($cartId);
    }

    private function getCheckout(): Checkout
    {
        return Checkout::withoutGlobalScopes()->findOrFail($this->checkoutId);
    }

    private function stepFromStatus(CheckoutStatus $status): string
    {
        return match ($status) {
            CheckoutStatus::Started => 'contact',
            CheckoutStatus::Addressed => 'shipping',
            CheckoutStatus::ShippingSelected => 'payment',
            default => 'contact',
        };
    }

    public function render(): mixed
    {
        $checkout = $this->getCheckout();
        $store = app('current_store');

        $availableRates = collect();

        if ($checkout->shipping_address_json) {
            $shippingCalculator = app(ShippingCalculator::class);
            $availableRates = $shippingCalculator->getAvailableRates($store, $checkout->shipping_address_json);
        }

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'totals' => $checkout->totals_json ?? [],
            'availableRates' => $availableRates,
        ])->layout('layouts::storefront');
    }
}
