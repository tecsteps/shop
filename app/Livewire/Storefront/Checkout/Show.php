<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart as CartModel;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public ?int $checkoutId = null;

    public string $email = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $address1 = '';

    public string $city = '';

    public string $province_code = '';

    public string $country_code = 'US';

    public string $postal_code = '';

    public ?int $shipping_rate_id = null;

    public string $discount_code = '';

    public string $discount_error = '';

    public string $payment_method = PaymentMethod::CreditCard->value;

    public function mount(): void
    {
        $store = app('current_store');
        $cartId = session('cart_id');

        if ($cartId === null) {
            Redirect::to('/cart')->send();

            return;
        }

        $cart = CartModel::query()->find($cartId);

        if ($cart === null || $cart->lines()->count() === 0) {
            Redirect::to('/cart')->send();

            return;
        }

        $checkout = app(CheckoutService::class)->start($store, $cart);
        $this->checkoutId = (int) $checkout->getKey();

        if ($checkout->email !== null) {
            $this->email = $checkout->email;
        }

        $address = $checkout->shipping_address_json ?? [];
        $this->first_name = (string) ($address['first_name'] ?? '');
        $this->last_name = (string) ($address['last_name'] ?? '');
        $this->address1 = (string) ($address['address1'] ?? '');
        $this->city = (string) ($address['city'] ?? '');
        $this->province_code = (string) ($address['province_code'] ?? '');
        $this->country_code = (string) ($address['country_code'] ?? 'US');
        $this->postal_code = (string) ($address['postal_code'] ?? '');

        $this->shipping_rate_id = $checkout->shipping_method_id ? (int) $checkout->shipping_method_id : null;
        $this->discount_code = $checkout->discount_code ?? '';
        $this->payment_method = $checkout->payment_method?->value ?? PaymentMethod::CreditCard->value;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address1' => 'required|string',
            'city' => 'required|string',
            'country_code' => 'required|string|size:2',
            'postal_code' => 'required|string',
        ]);

        $checkout = $this->loadCheckout();

        try {
            app(CheckoutService::class)->setAddress($checkout, [
                'email' => $this->email,
                'shipping_address' => $this->addressPayload(),
            ]);
        } catch (InvalidCheckoutStateException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function selectShipping(int $rateId): void
    {
        $checkout = $this->loadCheckout();
        $this->shipping_rate_id = $rateId;

        try {
            app(CheckoutService::class)->setShippingMethod($checkout, $rateId);
        } catch (InvalidCheckoutStateException $e) {
            $this->addError('shipping_rate_id', $e->getMessage());
        }
    }

    public function applyDiscount(): void
    {
        $this->discount_error = '';

        $checkout = $this->loadCheckout();

        try {
            app(CheckoutService::class)->applyDiscount($checkout, $this->discount_code);
        } catch (InvalidDiscountException $e) {
            $this->discount_error = $e->reason;
        }
    }

    public function removeDiscount(): void
    {
        $checkout = $this->loadCheckout();
        $this->discount_code = '';
        app(CheckoutService::class)->removeDiscount($checkout);
    }

    public function selectPayment(): void
    {
        $this->validate(['payment_method' => 'required|string']);

        $method = PaymentMethod::tryFrom($this->payment_method) ?? PaymentMethod::CreditCard;
        $checkout = $this->loadCheckout();

        try {
            app(CheckoutService::class)->selectPaymentMethod($checkout, $method);
        } catch (InvalidCheckoutStateException $e) {
            $this->addError('payment_method', $e->getMessage());
        }
    }

    public function render(): View
    {
        $checkout = $this->loadCheckout();
        $cart = $checkout->cart;
        $lines = $cart->lines()->with('variant.product')->get();
        $store = app('current_store');

        $rates = collect();

        if ($checkout->status !== CheckoutStatus::Started && ! empty($checkout->shipping_address_json)) {
            $rates = app(ShippingCalculator::class)->getAvailableRates(
                $store,
                $checkout->shipping_address_json ?? [],
            );
        }

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'cart' => $cart,
            'lines' => $lines,
            'rates' => $rates,
            'totals' => $checkout->totals_json ?? [],
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function addressPayload(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'address1' => $this->address1,
            'city' => $this->city,
            'province_code' => $this->province_code,
            'country_code' => $this->country_code,
            'postal_code' => $this->postal_code,
        ];
    }

    protected function loadCheckout(): Checkout
    {
        return Checkout::query()->findOrFail($this->checkoutId);
    }

    /**
     * Simple wrapper so tests can invoke a fresh cart creation when needed.
     */
    protected function cartService(): CartService
    {
        return app(CartService::class);
    }
}
