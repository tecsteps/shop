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

        $store = app('current_store');
        $rates = $shippingCalculator->getAvailableRates($store, $checkout->shipping_address_json);

        $this->availableShippingRates = $rates->map(fn ($rate) => [
            'id' => $rate->id,
            'name' => $rate->name,
            'amount' => $rate->config_json['amount'] ?? 0,
        ])->toArray();

        $this->step = 3;
    }

    public function submitShipping(CheckoutService $checkoutService): void
    {
        $this->validate([
            'selectedShippingRateId' => ['required', 'integer'],
        ]);

        $checkout = $this->getCheckout();
        $checkout = $checkoutService->setShippingMethod($checkout, $this->selectedShippingRateId);

        $this->totals = $checkout->totals_json ?? [];
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
        return Checkout::withoutGlobalScopes()->findOrFail($this->checkoutId);
    }
}
