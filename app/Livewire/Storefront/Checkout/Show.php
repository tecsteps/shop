<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use App\ValueObjects\ShippingRateOption;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public int $checkoutId;

    public string $currentStep = 'contact';

    public string $email = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $provinceCode = '';

    public string $country = 'DE';

    public string $postalCode = '';

    public string $phone = '';

    public ?int $selectedShippingMethodId = null;

    public string $selectedPaymentMethod = '';

    public string $discountCode = '';

    public string $discountError = '';

    public ?string $appliedDiscountCode = null;

    /** @var array<int, array<string, mixed>> */
    public array $availableShippingMethods = [];

    /** @var array<string, mixed> */
    public array $totals = [];

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
        $checkout = $this->findCheckout();

        if (! $checkout) {
            abort(404);
        }

        if ($checkout->status === CheckoutStatus::Expired) {
            abort(410, 'This checkout has expired.');
        }

        if ($checkout->status === CheckoutStatus::Completed) {
            $this->redirect(route('storefront.checkout.confirmation', $checkoutId));

            return;
        }

        $this->email = $checkout->email ?? '';
        $this->appliedDiscountCode = $checkout->discount_code;
        $this->totals = $checkout->totals_json ?? [];

        $this->resolveStep($checkout);
    }

    public function setAddress(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'max:20'],
        ]);

        $checkout = $this->findCheckout();
        $checkoutService = app(CheckoutService::class);

        try {
            $checkout = $checkoutService->setAddress($checkout, [
                'email' => $this->email,
                'shipping_address' => [
                    'first_name' => $this->firstName,
                    'last_name' => $this->lastName,
                    'address1' => $this->address1,
                    'address2' => $this->address2 ?: null,
                    'city' => $this->city,
                    'province' => $this->province ?: null,
                    'province_code' => $this->provinceCode ?: null,
                    'country' => $this->country,
                    'postal_code' => $this->postalCode,
                    'phone' => $this->phone ?: null,
                ],
            ]);

            $this->loadShippingMethods($checkout);
            $this->totals = $checkout->totals_json ?? [];
            $this->currentStep = 'shipping';
        } catch (InvalidCheckoutTransitionException $e) {
            $this->addError('checkout', $e->getMessage());
        }
    }

    public function setShippingMethod(): void
    {
        $checkout = $this->findCheckout();
        $checkoutService = app(CheckoutService::class);

        try {
            $checkout = $checkoutService->setShippingMethod($checkout, $this->selectedShippingMethodId);
            $this->totals = $checkout->totals_json ?? [];
            $this->currentStep = 'payment';
        } catch (InvalidCheckoutTransitionException $e) {
            $this->addError('checkout', $e->getMessage());
        }
    }

    public function selectPaymentMethod(): void
    {
        $this->validate([
            'selectedPaymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ]);

        $checkout = $this->findCheckout();
        $checkoutService = app(CheckoutService::class);

        try {
            $checkout = $checkoutService->selectPaymentMethod($checkout, $this->selectedPaymentMethod);
            $this->totals = $checkout->totals_json ?? [];
            $this->currentStep = 'review';
        } catch (InvalidCheckoutTransitionException $e) {
            $this->addError('checkout', $e->getMessage());
        }
    }

    public function applyDiscount(): void
    {
        $this->discountError = '';

        if (empty($this->discountCode)) {
            return;
        }

        $checkout = $this->findCheckout();
        $store = app('current_store');
        $cart = $checkout->cart()->with('lines')->first();

        $discountService = app(DiscountService::class);
        $result = $discountService->validate($this->discountCode, $store, $cart);

        if (! $result->valid) {
            $this->discountError = $result->errorMessage ?? 'Invalid discount code.';

            return;
        }

        $checkout->update(['discount_code' => $this->discountCode]);
        app(PricingEngine::class)->calculate($checkout->fresh());

        $this->appliedDiscountCode = $this->discountCode;
        $this->discountCode = '';
        $this->totals = $checkout->fresh()->totals_json ?? [];
    }

    public function removeDiscount(): void
    {
        $checkout = $this->findCheckout();
        $checkout->update(['discount_code' => null]);

        $cart = $checkout->cart()->with('lines')->first();
        foreach ($cart->lines as $line) {
            $line->update([
                'line_discount_amount' => 0,
                'line_total_amount' => $line->line_subtotal_amount,
            ]);
        }

        app(PricingEngine::class)->calculate($checkout->fresh());

        $this->appliedDiscountCode = null;
        $this->totals = $checkout->fresh()->totals_json ?? [];
    }

    protected function findCheckout(): Checkout
    {
        return Checkout::query()
            ->withoutGlobalScopes()
            ->where('id', $this->checkoutId)
            ->firstOrFail();
    }

    protected function resolveStep(Checkout $checkout): void
    {
        $this->currentStep = match ($checkout->status) {
            CheckoutStatus::Started => 'contact',
            CheckoutStatus::Addressed => 'shipping',
            CheckoutStatus::ShippingSelected => 'payment',
            CheckoutStatus::PaymentSelected => 'review',
            default => 'contact',
        };

        if ($checkout->status->value !== 'started') {
            $this->loadShippingMethods($checkout);
        }
    }

    protected function loadShippingMethods(Checkout $checkout): void
    {
        if (! $checkout->shipping_address_json) {
            return;
        }

        $store = app('current_store');
        $calculator = app(ShippingCalculator::class);
        $rates = $calculator->getAvailableRates($store, $checkout->shipping_address_json);

        $this->availableShippingMethods = $rates->map(fn (ShippingRateOption $rate) => [
            'id' => $rate->id,
            'name' => $rate->name,
            'amount' => $rate->amount,
            'type' => $rate->type,
        ])->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.checkout.show');
    }
}
