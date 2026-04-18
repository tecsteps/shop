<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public ?int $checkoutId = null;

    public string $email = '';

    public array $address = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province_code' => '',
        'zip' => '',
        'country_code' => 'US',
        'phone' => '',
    ];

    public ?int $shippingMethodId = null;

    public string $paymentMethod = 'credit_card';

    public array $errorMessages = [];

    public function mount(): void
    {
        $checkout = $this->ensureCheckout();
        $this->checkoutId = $checkout->id;
        $this->email = (string) ($checkout->email ?? '');
        if ($checkout->shipping_address_json) {
            $this->address = array_merge($this->address, $checkout->shipping_address_json);
        }
        $this->shippingMethodId = $checkout->shipping_method_id;
        if ($checkout->payment_method) {
            $this->paymentMethod = $checkout->payment_method;
        }
    }

    public function submitAddress(): void
    {
        $this->errorMessages = [];

        try {
            app(CheckoutService::class)->setAddress($this->checkout(), [
                'email' => $this->email,
                'shipping_address' => $this->address,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessages = collect($e->errors())->flatten()->all();
        }
    }

    public function selectShipping(int $rateId): void
    {
        $this->shippingMethodId = $rateId;

        try {
            app(CheckoutService::class)->setShippingMethod($this->checkout(), $rateId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessages = collect($e->errors())->flatten()->all();
        }
    }

    public function placeOrder()
    {
        $checkout = $this->checkout();

        try {
            app(CheckoutService::class)->selectPaymentMethod($checkout, $this->paymentMethod);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessages = collect($e->errors())->flatten()->all();

            return null;
        }

        $this->dispatch('checkout:payment-pending', checkoutId: $checkout->id);

        return null;
    }

    public function render()
    {
        $checkout = $this->checkout();
        $pricing = app(PricingEngine::class)->calculate($checkout);

        $availableRates = [];
        if ($checkout->status !== CheckoutStatus::Started) {
            $cart = $checkout->cart()->with('lines.variant')->first();
            $availableRates = app(ShippingCalculator::class)
                ->getAvailableRates(app('current_store'), $checkout->shipping_address_json ?? [], $cart)
                ->map(fn ($rate) => [
                    'id' => $rate->id,
                    'name' => $rate->name,
                    'amount' => app(ShippingCalculator::class)->calculate($rate, $cart) ?? 0,
                ])
                ->values()
                ->all();
        }

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'pricing' => $pricing,
            'availableRates' => $availableRates,
        ]);
    }

    protected function checkout(): Checkout
    {
        $id = $this->checkoutId ?? session('checkout_id');
        $checkout = Checkout::query()->with(['cart.lines.variant.product'])->find($id);
        if (! $checkout) {
            throw new NotFoundHttpException('Checkout not found');
        }

        return $checkout;
    }

    protected function ensureCheckout(): Checkout
    {
        $id = session('checkout_id');
        if ($id) {
            $checkout = Checkout::query()->find($id);
            if ($checkout && $checkout->status !== CheckoutStatus::Completed) {
                return $checkout;
            }
        }

        $cart = app(CartService::class)->getOrCreateForSession(app('current_store'));
        $checkout = app(CheckoutService::class)->startFromCart($cart);
        session()->put('checkout_id', $checkout->id);

        return $checkout;
    }
}
