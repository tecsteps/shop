<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Livewire\Component;

class Show extends Component
{
    public Checkout $checkout;

    public int $currentStep = 1;

    // Step 1: Contact & Address
    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $postalCode = '';

    public string $country = 'DE';

    public string $phone = '';

    // Step 2: Shipping
    public ?int $selectedShippingRate = null;

    /** @var array<int, array{id: int, name: string, price: int|null}> */
    public array $availableShippingRates = [];

    // Step 3: Payment
    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardExpiry = '';

    public string $cardCvv = '';

    public function mount(int $checkoutId): void
    {
        $checkout = Checkout::with('cart.lines.variant.product')->find($checkoutId);

        if (! $checkout) {
            abort(404);
        }

        if ($checkout->status === CheckoutStatus::Completed) {
            $this->redirect(route('storefront.checkout.confirmation', $checkout->id), navigate: true);

            return;
        }

        $this->checkout = $checkout;

        // Pre-fill from existing checkout data
        if ($checkout->email) {
            $this->email = $checkout->email;
        }

        if ($address = $checkout->shipping_address_json) {
            $this->firstName = $address['first_name'] ?? '';
            $this->lastName = $address['last_name'] ?? '';
            $this->address1 = $address['address1'] ?? '';
            $this->address2 = $address['address2'] ?? '';
            $this->city = $address['city'] ?? '';
            $this->province = $address['province'] ?? '';
            $this->postalCode = $address['postal_code'] ?? '';
            $this->country = $address['country'] ?? 'DE';
            $this->phone = $address['phone'] ?? '';
        }

        // Pre-fill email for logged-in customer
        if (! $this->email && auth('customer')->check()) {
            $this->email = auth('customer')->user()->email ?? '';
        }

        // Set current step based on checkout status
        $this->currentStep = match ($checkout->status) {
            CheckoutStatus::Addressed => 2,
            CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected => 3,
            default => 1,
        };

        if ($this->currentStep >= 2) {
            $this->loadShippingRates();
        }
    }

    public function continueToShipping(): void
    {
        $this->validate([
            'email' => 'required|email',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postalCode' => 'required|string|max:20',
            'country' => 'required|string|size:2',
        ]);

        $checkoutService = app(CheckoutService::class);

        // Reset status if going back to address step
        if ($this->checkout->status !== CheckoutStatus::Started) {
            $this->checkout->update(['status' => CheckoutStatus::Started]);
            $this->checkout->refresh();
        }

        $checkoutService->setAddress($this->checkout, [
            'email' => $this->email,
            'shipping_address' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'address1' => $this->address1,
                'address2' => $this->address2,
                'city' => $this->city,
                'province' => $this->province,
                'postal_code' => $this->postalCode,
                'country' => $this->country,
                'phone' => $this->phone,
            ],
        ]);

        $this->checkout->refresh();
        $this->loadShippingRates();
        $this->currentStep = 2;
    }

    public function continueToPayment(): void
    {
        $this->validate([
            'selectedShippingRate' => 'required|integer',
        ]);

        $checkoutService = app(CheckoutService::class);

        // Reset to addressed if needed
        if ($this->checkout->status !== CheckoutStatus::Addressed) {
            $this->checkout->update(['status' => CheckoutStatus::Addressed]);
            $this->checkout->refresh();
        }

        $checkoutService->setShippingMethod($this->checkout, $this->selectedShippingRate);

        $this->checkout->refresh();
        $this->currentStep = 3;
    }

    public function placeOrder(): void
    {
        $this->validate([
            'paymentMethod' => 'required|string|in:credit_card,paypal,bank_transfer',
        ]);

        $checkoutService = app(CheckoutService::class);
        $paymentMethodEnum = PaymentMethod::from($this->paymentMethod);

        try {
            // Reset to shipping_selected if needed
            if ($this->checkout->status !== CheckoutStatus::ShippingSelected) {
                $this->checkout->update(['status' => CheckoutStatus::ShippingSelected]);
                $this->checkout->refresh();
            }

            $checkoutService->selectPaymentMethod($this->checkout, $paymentMethodEnum);
            $this->checkout->refresh();

            // Calculate final pricing
            $pricingEngine = app(PricingEngine::class);
            $pricingEngine->calculate($this->checkout);
            $this->checkout->refresh();

            // Complete checkout with payment
            $paymentDetails = [];
            if ($paymentMethodEnum === PaymentMethod::CreditCard) {
                $paymentDetails = [
                    'card_number' => $this->cardNumber,
                    'card_expiry' => $this->cardExpiry,
                    'card_cvv' => $this->cardCvv,
                ];
            }

            $order = $checkoutService->completeCheckout($this->checkout, $paymentDetails);

            $this->redirect(route('storefront.checkout.confirmation', $this->checkout->id), navigate: true);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to complete checkout: '.$e->getMessage());
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    private function loadShippingRates(): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        if (! $store) {
            return;
        }

        $address = $this->checkout->shipping_address_json ?? [];
        $countryCode = $address['country'] ?? $this->country;

        $calculator = app(ShippingCalculator::class);
        $cart = $this->checkout->cart;
        $rates = $calculator->getAvailableRates($store, ['country' => $countryCode]);

        $this->availableShippingRates = $rates->map(fn ($rate) => [
            'id' => $rate->id,
            'name' => $rate->name,
            'price' => $calculator->calculate($rate, $cart),
        ])->toArray();

        // Pre-select if only one rate
        if (count($this->availableShippingRates) === 1 && ! $this->selectedShippingRate) {
            $this->selectedShippingRate = $this->availableShippingRates[0]['id'];
        }
    }

    public function render(): mixed
    {
        $cart = $this->checkout->cart;
        $cart->load('lines.variant.product');
        $lines = $cart->lines;
        $subtotal = $lines->sum('line_total_amount');
        $currency = $cart->currency;

        $totals = $this->checkout->totals_json;

        return view('livewire.storefront.checkout.show', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'currency' => $currency,
            'totals' => $totals,
        ])->layout('layouts.storefront', ['title' => 'Checkout']);
    }
}
