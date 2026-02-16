<?php

namespace App\Livewire\Storefront\Checkout;

use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ShippingCalculator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public int $step = 1;

    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $country = 'DE';

    public string $zip = '';

    public string $phone = '';

    public ?int $selectedShippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public ?int $checkoutId = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateForSession($store);

        if ($cart->lines()->count() === 0) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        $checkoutService = app(CheckoutService::class);
        $existingCheckout = \App\Models\Checkout::query()
            ->where('cart_id', $cart->id)
            ->whereNotIn('status', [
                \App\Enums\CheckoutStatus::Completed->value,
                \App\Enums\CheckoutStatus::Expired->value,
            ])
            ->latest()
            ->first();

        $checkout = $existingCheckout ?? $checkoutService->createFromCart($cart);
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
            'country' => 'required|string|max:2',
            'zip' => 'required|string|max:20',
        ]);

        $checkoutService = app(CheckoutService::class);
        $checkout = \App\Models\Checkout::query()->findOrFail($this->checkoutId);

        $checkoutService->setAddress($checkout, [
            'email' => $this->email,
            'shipping_address' => [
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'address1' => $this->address1,
                'address2' => $this->address2,
                'city' => $this->city,
                'province' => $this->province,
                'country_code' => $this->country,
                'zip' => $this->zip,
                'phone' => $this->phone,
            ],
        ]);

        $this->step = 2;
    }

    public function submitShipping(): void
    {
        $this->validate([
            'selectedShippingRateId' => 'required|integer',
        ]);

        $checkoutService = app(CheckoutService::class);
        $checkout = \App\Models\Checkout::query()->findOrFail($this->checkoutId);

        $checkoutService->setShippingMethod($checkout, $this->selectedShippingRateId);

        $this->step = 3;
    }

    public function submitPayment(): void
    {
        $checkoutService = app(CheckoutService::class);
        $orderService = app(OrderService::class);
        $checkout = \App\Models\Checkout::query()->findOrFail($this->checkoutId);

        $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);
        $checkout->refresh();

        try {
            $order = $orderService->createFromCheckout($checkout, [
                'card_number' => str_replace(' ', '', $this->cardNumber),
            ]);
            session()->flash('order_id', $order->id);
            $this->redirect(route('storefront.checkout.confirmation'));
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): mixed
    {
        $store = app('current_store');
        $checkout = $this->checkoutId
            ? \App\Models\Checkout::query()->with('cart.lines.variant.product')->find($this->checkoutId)
            : null;

        $shippingRates = collect();
        if ($this->step >= 2 && $checkout) {
            $calculator = app(ShippingCalculator::class);
            $shippingRates = $calculator->getAvailableRates($store, $checkout->shipping_address_json ?? []);
        }

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'shippingRates' => $shippingRates,
        ]);
    }
}
