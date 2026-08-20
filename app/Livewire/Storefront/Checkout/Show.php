<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\PaymentMethod;
use App\Models\Checkout as CheckoutModel;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Livewire\Component;

class Show extends Component
{
    public CheckoutModel $checkout;

    public array $shippingAddress = ['first_name' => '', 'last_name' => '', 'address1' => '', 'city' => '', 'country_code' => 'DE', 'postal_code' => ''];

    public ?int $shippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '4242424242424242';

    public string $message = '';

    public function mount(int $checkoutId): void
    {
        $this->checkout = CheckoutModel::query()->with(['cart.lines.variant.product', 'shippingRate'])->findOrFail($checkoutId);
        $customerId = auth('customer')->id();
        $sessionCartIds = array_filter([session('cart_id'), session('cart_id_'.app('current_store')->getKey())]);
        abort_unless(($customerId !== null && (int) $this->checkout->customer_id === (int) $customerId)
            || ($customerId === null && in_array($this->checkout->cart_id, $sessionCartIds, true)), 404);

        if ($this->checkout->shipping_address_json !== null) {
            $this->shippingAddress = $this->checkout->shipping_address_json;
        }
    }

    public function saveAddress(CheckoutService $checkouts): void
    {
        $this->checkout = $checkouts->setAddress($this->checkout, $this->shippingAddress);
        $this->message = 'Address saved';
    }

    public function chooseShipping(CheckoutService $checkouts): void
    {
        $this->validate(['shippingRateId' => ['required', 'integer']]);
        $this->checkout = $checkouts->setShippingMethod($this->checkout, $this->shippingRateId);
        $this->message = 'Shipping method saved';
    }

    public function pay(CheckoutService $checkouts, PaymentService $payments): void
    {
        $this->validate(['paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer']]);
        $this->checkout = $checkouts->selectPaymentMethod($this->checkout, $this->paymentMethod);
        $order = $payments->pay($this->checkout, PaymentMethod::from($this->paymentMethod), ['card_number' => $this->cardNumber]);

        if ($order === null) {
            $this->addError('paymentMethod', 'Your payment was declined.');

            return;
        }

        $this->redirect(route('checkout.confirmation', ['checkoutId' => $order->checkout_id]), navigate: true);
    }

    public function render(ShippingCalculator $shipping, PricingEngine $pricing): mixed
    {
        $rates = $this->checkout->shipping_address_json === null ? collect() : $shipping->getAvailableRates(app('current_store'), $this->checkout->shipping_address_json);
        $this->checkout->load(['cart.lines.variant.product', 'shippingRate']);
        $totals = $this->checkout->totals_json ?? $pricing->calculate($this->checkout)->toArray();

        return view('livewire.storefront.checkout.show', compact('rates', 'totals'))->layout('layouts.storefront');
    }
}
