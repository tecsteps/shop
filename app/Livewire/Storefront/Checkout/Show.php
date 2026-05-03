<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\UnavailableShippingRateException;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public int $checkoutId;

    /**
     * @var array<string, string|null>
     */
    public array $shippingAddress = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'postal_code' => '',
        'phone' => '',
    ];

    public ?int $selectedShippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $discountCode = '';

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
        $checkout = $this->checkout();

        if (is_array($checkout->shipping_address_json)) {
            $this->shippingAddress = array_merge($this->shippingAddress, $checkout->shipping_address_json);
        }

        $this->selectedShippingRateId = $checkout->shipping_method_id;
        $this->paymentMethod = $checkout->payment_method?->value ?? PaymentMethod::CreditCard->value;
        $this->discountCode = $checkout->discount_code ?? '';
    }

    public function saveAddress(): void
    {
        $this->validate([
            'shippingAddress.first_name' => ['required', 'string', 'max:255'],
            'shippingAddress.last_name' => ['required', 'string', 'max:255'],
            'shippingAddress.address1' => ['required', 'string', 'max:500'],
            'shippingAddress.city' => ['required', 'string', 'max:255'],
            'shippingAddress.country_code' => ['required', 'string', 'size:2'],
            'shippingAddress.postal_code' => ['required', 'string', 'max:20'],
        ]);

        try {
            app(CheckoutService::class)->setAddress($this->checkout(), [
                'shipping_address' => $this->shippingAddress,
                'use_shipping_as_billing' => true,
            ]);
        } catch (CheckoutStateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function selectShipping(): void
    {
        $this->validate([
            'selectedShippingRateId' => ['required', 'integer'],
        ]);

        try {
            app(CheckoutService::class)->setShippingMethod($this->checkout(), (int) $this->selectedShippingRateId);
        } catch (CheckoutStateException|UnavailableShippingRateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function applyDiscount(): void
    {
        $this->validate([
            'discountCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            app(CheckoutService::class)->applyDiscount($this->checkout(), $this->discountCode);
        } catch (CheckoutStateException|InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(): void
    {
        try {
            app(CheckoutService::class)->removeDiscount($this->checkout());
            $this->discountCode = '';
        } catch (CheckoutStateException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function selectPayment(): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ]);

        try {
            app(CheckoutService::class)->selectPaymentMethod(
                $this->checkout(),
                PaymentMethod::from($this->paymentMethod),
            );
            session()->flash('checkout_status', 'Payment method saved.');
        } catch (CheckoutStateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $checkout = $this->checkout();
        $shippingMethods = $checkout->shipping_address_json
            ? app(ShippingCalculator::class)->getAvailableRateQuotes(app('current_store'), $checkout->cart, $checkout->shipping_address_json)
            : collect();

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'shippingMethods' => $shippingMethods,
            'totals' => $checkout->totals_json ?? [],
        ])->layout('storefront.layouts.app', [
            'title' => 'Checkout',
        ]);
    }

    private function checkout(): Checkout
    {
        return Checkout::withoutGlobalScopes()
            ->with('cart.lines.variant.product.media', 'cart.lines.variant.optionValues.option', 'cart.lines.variant.inventoryItem', 'shippingRate')
            ->where('store_id', app('current_store')->id)
            ->whereKey($this->checkoutId)
            ->firstOrFail();
    }
}
