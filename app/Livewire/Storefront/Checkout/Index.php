<?php

namespace App\Livewire\Storefront\Checkout;

use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    public int $step = 1;

    public ?int $checkoutId = null;

    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $postalCode = '';

    public string $countryCode = 'DE';

    public string $phone = '';

    public ?int $selectedShippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardholderName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $error = '';

    /** @var array<int, array<string, mixed>> */
    public array $availableShippingRates = [];

    /** @var array<string, mixed> */
    public array $totals = [];

    public function mount(CartService $cartService, CheckoutService $checkoutService): void
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');
        $cart = $cartService->getOrCreateForSession($store);

        if ($cart->lines()->count() === 0) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        $checkout = $checkoutService->createCheckout($cart);

        $discountCode = session('discount_code');
        if ($discountCode) {
            $checkout->update(['discount_code' => $discountCode]);
        }

        $this->checkoutId = $checkout->id;
    }

    public function submitContact(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $checkout = $this->getCheckout();
        $checkout->update(['email' => $this->email]);

        $this->step = 2;
    }

    public function submitAddress(CheckoutService $checkoutService, ShippingCalculator $shippingCalculator): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'max:20'],
            'countryCode' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $checkout = $this->getCheckout();

        $addressData = [
            'email' => $this->email,
            'shipping_address' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'address1' => $this->address1,
                'address2' => $this->address2,
                'city' => $this->city,
                'postal_code' => $this->postalCode,
                'country_code' => $this->countryCode,
                'phone' => $this->phone,
            ],
        ];

        $checkout = $checkoutService->setAddress($checkout, $addressData);

        /** @var \App\Models\Store $store */
        $store = app('current_store');
        /** @var array{country_code?: string, province_code?: string} $shippingAddress */
        $shippingAddress = $checkout->shipping_address_json ?? [];
        $rates = $shippingCalculator->getAvailableRates($store, $shippingAddress);

        /** @var array<int, array<string, mixed>> $mappedRates */
        $mappedRates = $rates->map(function (\App\Models\ShippingRate $rate): array {
            /** @var array<string, mixed> $configJson */
            $configJson = $rate->config_json ?? [];

            return [
                'id' => $rate->id,
                'name' => $rate->name,
                'amount' => $configJson['amount'] ?? 0,
            ];
        })->toArray();
        $this->availableShippingRates = $mappedRates;

        $this->step = 3;
    }

    public function submitShipping(CheckoutService $checkoutService): void
    {
        $this->validate([
            'selectedShippingRateId' => ['required', 'integer'],
        ]);

        $checkout = $this->getCheckout();

        /** @var int $rateId */
        $rateId = $this->selectedShippingRateId;
        $checkout = $checkoutService->setShippingMethod($checkout, $rateId);

        /** @var array<string, mixed> $totalsJson */
        $totalsJson = $checkout->totals_json ?? [];
        $this->totals = $totalsJson;
        $this->step = 4;
    }

    public function submitPayment(CheckoutService $checkoutService): void
    {
        $rules = [
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ];

        if ($this->paymentMethod === 'credit_card') {
            $rules['cardNumber'] = ['required', 'string'];
            $rules['cardholderName'] = ['required', 'string'];
            $rules['cardExpiry'] = ['required', 'string'];
            $rules['cardCvc'] = ['required', 'string'];
        }

        $this->validate($rules);

        $checkout = $this->getCheckout();

        $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);
        /** @var Checkout $checkout */
        $checkout = $checkout->fresh();

        $paymentDetails = [];
        if ($this->paymentMethod === 'credit_card') {
            $paymentDetails = [
                'card_number' => $this->cardNumber,
                'cardholder_name' => $this->cardholderName,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
            ];
        }

        try {
            $order = $checkoutService->completeCheckout($checkout, $paymentDetails);

            session()->forget('cart_id');
            session()->forget('discount_code');

            $this->redirect(route('checkout.confirmation', ['orderNumber' => ltrim($order->order_number, '#')]));
        } catch (PaymentFailedException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function render(): View
    {
        $checkout = $this->checkoutId ? $this->getCheckout() : null;
        $cartLines = [];

        if ($checkout) {
            $cart = $checkout->cart()->withoutGlobalScopes()->with('lines.variant.product')->first();
            $cartLines = $cart ? $cart->lines : collect();
        }

        return view('livewire.storefront.checkout.index', [
            'checkout' => $checkout,
            'cartLines' => $cartLines,
        ]);
    }

    private function getCheckout(): Checkout
    {
        /** @var \App\Models\Store $store */
        $store = app('current_store');

        /** @var Checkout */
        return Checkout::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($this->checkoutId);
    }
}
