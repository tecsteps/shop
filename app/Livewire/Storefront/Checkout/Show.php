<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Show extends Component
{
    public Checkout $checkout;

    public string $email = '';

    /** @var array<string, string> */
    public array $shipping = ['first_name' => '', 'last_name' => '', 'address1' => '', 'address2' => '', 'city' => '', 'province_code' => '', 'country' => 'DE', 'postal_code' => '', 'phone' => ''];

    public ?int $shippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '4242 4242 4242 4242';

    public string $discountCode = '';

    public function mount(int $checkoutId): void
    {
        $this->checkout = Checkout::query()->with(['cart.lines.variant.product', 'shippingMethod'])
            ->whereKey($checkoutId)->firstOrFail();
        abort_unless($this->checkout->store_id === app('current_store')->id, 404);
        $this->email = $this->checkout->email ?? auth('customer')->user()?->email ?? '';
        $this->shipping = array_merge($this->shipping, $this->checkout->shipping_address_json ?? []);
        $this->shippingRateId = $this->checkout->shipping_method_id;
        $this->paymentMethod = $this->checkout->payment_method?->value ?? PaymentMethod::CreditCard->value;
    }

    public function saveAddress(CheckoutService $checkouts): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'shipping.first_name' => ['required', 'string', 'max:255'],
            'shipping.last_name' => ['required', 'string', 'max:255'],
            'shipping.address1' => ['required', 'string', 'max:255'],
            'shipping.city' => ['required', 'string', 'max:255'],
            'shipping.country' => ['required', 'string', 'size:2'],
            'shipping.postal_code' => ['required', 'string', 'max:32'],
        ]);
        $this->checkout = $checkouts->setAddress($this->checkout, ['email' => $this->email, 'shipping_address' => $this->shipping]);
    }

    public function selectShipping(CheckoutService $checkouts): void
    {
        $this->validate(['shippingRateId' => ['nullable', 'integer']]);
        $this->checkout = $checkouts->setShippingMethod($this->checkout, $this->shippingRateId);
    }

    public function selectPayment(CheckoutService $checkouts): void
    {
        $this->validate(['paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer']]);
        $this->checkout = $checkouts->selectPaymentMethod($this->checkout, $this->paymentMethod);
    }

    public function applyDiscount(PricingEngine $pricing): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:50']]);
        $this->checkout->update(['discount_code' => $this->discountCode]);
        $pricing->calculate($this->checkout->refresh());
        session()->flash('storefront_status', 'Discount applied');
    }

    public function pay(CheckoutService $checkouts): void
    {
        $details = $this->paymentMethod === PaymentMethod::CreditCard->value ? ['card_number' => $this->cardNumber] : [];

        try {
            $order = $checkouts->completeCheckout($this->checkout, $details);
        } catch (PaymentFailedException $exception) {
            $this->addError('cardNumber', $exception->getMessage());

            return;
        }

        $this->redirectRoute('storefront.checkout.confirmation', ['checkoutId' => $this->checkout->id, 'order' => $order->id]);
    }

    public function render(ShippingCalculator $shipping): View
    {
        $rates = in_array($this->checkout->status, [CheckoutStatus::Addressed, CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected], true)
            ? $shipping->getAvailableRates(app('current_store'), $this->checkout->shipping_address_json ?? [])
            : collect();

        return view('livewire.storefront.checkout.show', ['rates' => $rates])
            ->layout('layouts.storefront', ['title' => 'Checkout - '.app('current_store')->name]);
    }
}
