<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public ?int $checkoutId = null;

    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $country = 'US';

    public string $postalCode = '';

    public string $province = '';

    public ?int $selectedShippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public function mount(): void
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        if (! $store) {
            return;
        }

        $cart = $this->getCart();

        if (! $cart || $cart->lines()->count() === 0) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        $checkoutService = app(CheckoutService::class);
        $checkout = $checkoutService->createCheckout($cart);
        $this->checkoutId = $checkout->id;
    }

    public function submitAddress(): void
    {
        $this->validate([
            'email' => 'required|email',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'postalCode' => 'required|string|max:20',
        ]);

        $checkout = Checkout::withoutGlobalScopes()->findOrFail($this->checkoutId);
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
                'province' => $this->province,
            ],
        ]);
    }

    public function selectShipping(): void
    {
        $checkout = Checkout::withoutGlobalScopes()->findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $cart = Cart::withoutGlobalScopes()->findOrFail($checkout->cart_id);
        $shippingCalculator = app(ShippingCalculator::class);

        if (! $shippingCalculator->cartRequiresShipping($cart)) {
            $checkoutService->skipShipping($checkout);

            return;
        }

        if ($this->selectedShippingRateId) {
            $checkoutService->setShippingMethod($checkout, $this->selectedShippingRateId);
        }
    }

    public function submitPayment(): void
    {
        $checkout = Checkout::withoutGlobalScopes()->findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);
        $checkoutService->completeCheckout($checkout);

        $this->redirect(route('storefront.checkout.confirmation', ['checkout' => $checkout->id]));
    }

    public function render(): View
    {
        $checkout = $this->checkoutId
            ? Checkout::withoutGlobalScopes()->find($this->checkoutId)
            : null;

        $availableRates = collect();

        if ($checkout && $checkout->shipping_address_json) {
            $store = $checkout->store()->withoutGlobalScopes()->first();
            $shippingCalculator = app(ShippingCalculator::class);
            $availableRates = $shippingCalculator->getAvailableRates($store, $checkout->shipping_address_json);
        }

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'availableRates' => $availableRates,
        ])->layout('storefront.layouts.app', [
            'title' => 'Checkout',
        ]);
    }

    private function getCart(): ?Cart
    {
        $store = app('current_store');
        $customer = auth('customer')->user();

        if ($customer) {
            return Cart::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('customer_id', $customer->id)
                ->where('status', 'active')
                ->first();
        }

        $cartId = session('cart_id');

        if (! $cartId) {
            return null;
        }

        return Cart::withoutGlobalScopes()
            ->where('id', $cartId)
            ->where('store_id', $store->id)
            ->where('status', 'active')
            ->first();
    }
}
