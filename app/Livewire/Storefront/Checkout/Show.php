<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    public Checkout $checkout;

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string|max:255')]
    public string $firstName = '';

    #[Validate('required|string|max:255')]
    public string $lastName = '';

    #[Validate('required|string|max:255')]
    public string $address1 = '';

    #[Validate('nullable|string|max:255')]
    public string $address2 = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('nullable|string|max:255')]
    public string $province = '';

    #[Validate('required|string|max:255')]
    public string $postalCode = '';

    #[Validate('required|string|size:2')]
    public string $country = 'DE';

    #[Validate('nullable|string|max:50')]
    public string $phone = '';

    public ?int $selectedShippingRateId = null;

    public string $selectedPaymentMethod = 'credit_card';

    #[Validate('required_if:selectedPaymentMethod,credit_card|digits:16')]
    public string $cardNumber = '';

    #[Validate('required_if:selectedPaymentMethod,credit_card|max:255')]
    public string $cardholderName = '';

    #[Validate('required_if:selectedPaymentMethod,credit_card|regex:/^\d{2}\/\d{2}$/')]
    public string $cardExpiry = '';

    #[Validate('required_if:selectedPaymentMethod,credit_card|digits_between:3,4')]
    public string $cardCvc = '';

    public ?string $paymentError = null;

    public bool $showOrderSummary = false;

    public string $discountCode = '';

    public ?string $discountError = null;

    public function mount(Checkout $checkout): void
    {
        if ($checkout->status === CheckoutStatus::Completed) {
            $this->redirect(route('storefront.checkout.confirmation', $checkout));

            return;
        }

        $this->checkout = $checkout;

        if ($checkout->totals_json === null) {
            app(PricingEngine::class)->calculate($checkout);
            $this->checkout = $checkout->refresh();
        }

        $this->email = $checkout->email ?? '';

        $address = $checkout->shipping_address_json ?? [];
        $this->firstName = $address['first_name'] ?? '';
        $this->lastName = $address['last_name'] ?? '';
        $this->address1 = $address['address1'] ?? '';
        $this->address2 = $address['address2'] ?? '';
        $this->city = $address['city'] ?? '';
        $this->province = $address['province'] ?? '';
        $this->postalCode = $address['postal_code'] ?? '';
        $this->country = $address['country'] ?? 'DE';
        $this->phone = $address['phone'] ?? '';
        $this->selectedShippingRateId = $checkout->shipping_method_id;
        $this->discountCode = $checkout->discount_code ?? '';
    }

    public function applyDiscount(): void
    {
        $this->discountError = null;

        if ($this->discountCode === '') {
            return;
        }

        try {
            app(DiscountService::class)->validate($this->discountCode, $this->checkout->store, $this->checkout->cart);
        } catch (InvalidDiscountException $exception) {
            $this->discountError = str($exception->reason)->replace('_', ' ')->ucfirst()->value();

            return;
        }

        $this->checkout->update(['discount_code' => $this->discountCode]);
        app(PricingEngine::class)->calculate($this->checkout);
        $this->checkout = $this->checkout->refresh();
    }

    public function removeDiscount(): void
    {
        $this->discountCode = '';
        $this->discountError = null;
        $this->checkout->update(['discount_code' => null]);
        app(PricingEngine::class)->calculate($this->checkout);
        $this->checkout = $this->checkout->refresh();
    }

    #[Computed]
    public function step(): int
    {
        return match ($this->checkout->status) {
            CheckoutStatus::Started => 1,
            CheckoutStatus::Addressed => 2,
            CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected => 3,
            default => 1,
        };
    }

    #[Computed]
    public function requiresShipping(): bool
    {
        $this->checkout->loadMissing('cart.lines.variant');

        return $this->checkout->cart->lines->contains(fn ($line): bool => $line->variant->requires_shipping);
    }

    #[Computed]
    public function availableShippingRates(): Collection
    {
        if ($this->checkout->status === CheckoutStatus::Started) {
            return collect();
        }

        return app(ShippingCalculator::class)->getAvailableRates(
            $this->checkout->store,
            $this->checkout->shipping_address_json ?? [],
        );
    }

    public function saveAddress(): void
    {
        $this->validate([
            'email' => 'required|email',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postalCode' => 'required|string|max:255',
            'country' => 'required|string|size:2',
        ]);

        try {
            $this->checkout = app(CheckoutService::class)->setAddress($this->checkout, [
                'email' => $this->email,
                'shipping_address' => [
                    'first_name' => $this->firstName,
                    'last_name' => $this->lastName,
                    'address1' => $this->address1,
                    'address2' => $this->address2,
                    'city' => $this->city,
                    'province' => $this->province,
                    'postal_code' => $this->postalCode,
                    'country' => strtoupper($this->country),
                    'phone' => $this->phone,
                ],
            ]);
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->addError('address', $exception->getMessage());

            return;
        }

        if (! $this->requiresShipping) {
            $this->checkout = app(CheckoutService::class)->setShippingMethod($this->checkout, null);
        }

        unset($this->availableShippingRates, $this->requiresShipping);
    }

    public function editAddress(): void
    {
        $this->checkout->update(['status' => CheckoutStatus::Started]);
        $this->checkout = $this->checkout->refresh();
    }

    public function selectShippingRate(int $rateId): void
    {
        $this->selectedShippingRateId = $rateId;

        try {
            $this->checkout = app(CheckoutService::class)->setShippingMethod($this->checkout, $rateId);
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->addError('shipping', $exception->getMessage());
        }
    }

    public function editShipping(): void
    {
        $this->checkout->update(['status' => CheckoutStatus::Addressed]);
        $this->checkout = $this->checkout->refresh();
    }

    public function pay(): void
    {
        $this->paymentError = null;

        if ($this->selectedPaymentMethod === PaymentMethod::CreditCard->value) {
            $this->validate([
                'cardNumber' => 'required|digits:16',
                'cardholderName' => 'required|string|max:255',
                'cardExpiry' => 'required|regex:/^\d{2}\/\d{2}$/',
                'cardCvc' => 'required|digits_between:3,4',
            ]);
        }

        $checkoutService = app(CheckoutService::class);

        try {
            if ($this->checkout->status !== CheckoutStatus::PaymentSelected) {
                $this->checkout = $checkoutService->selectPaymentMethod($this->checkout, $this->selectedPaymentMethod);
            }

            $order = $checkoutService->completeCheckout($this->checkout, [
                'card_number' => $this->cardNumber,
                'cardholder_name' => $this->cardholderName,
                'expiry' => $this->cardExpiry,
                'cvc' => $this->cardCvc,
            ]);
        } catch (PaymentFailedException $exception) {
            $this->checkout = $this->checkout->refresh();
            $this->paymentError = match ($exception->reason) {
                'card_declined' => 'The card was declined.',
                'insufficient_funds' => 'The card has insufficient funds.',
                default => 'Payment failed. Please try again.',
            };

            return;
        } catch (InvalidCheckoutTransitionException $exception) {
            $this->paymentError = $exception->getMessage();

            return;
        }

        session()->forget(['cart_id', 'cart_discount_code']);

        $this->redirect(route('storefront.checkout.confirmation', $order->checkout_id));
    }

    public function render()
    {
        return view('livewire.storefront.checkout.show')
            ->layout('layouts.storefront')
            ->title('Checkout - '.app('current_store')->name);
    }
}
