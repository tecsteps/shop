<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidDiscountException;
use App\Models\Checkout as CheckoutModel;
use App\Models\CustomerAddress;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PaymentService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Carbon\Carbon;
use Livewire\Component;
use Throwable;

class Show extends Component
{
    public CheckoutModel $checkout;

    public string $email = '';

    /** @var array<string, string> */
    public array $shippingAddress = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'state' => '',
        'country_code' => 'DE',
        'postal_code' => '',
        'phone' => '',
    ];

    /** @var array<string, string> */
    public array $billingAddress = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'state' => '',
        'country_code' => 'DE',
        'postal_code' => '',
        'phone' => '',
    ];

    public bool $billingSameAsShipping = true;

    public ?int $savedAddressId = null;

    public ?int $shippingRateId = null;

    public string $paymentMethod = PaymentMethod::CreditCard->value;

    public string $cardNumber = '4242424242424242';

    public string $cardholderName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $discountCode = '';

    public string $message = '';

    public bool $processing = false;

    public int $activeStep = 1;

    public bool $showOrderSummary = false;

    public function mount(int $checkoutId): void
    {
        $this->checkout = CheckoutModel::query()
            ->with(['cart.lines.variant.product.media', 'shippingRate'])
            ->findOrFail($checkoutId);

        $customerId = auth('customer')->id();
        $sessionCartIds = array_filter([
            session('cart_id'),
            session('cart_id_'.app('current_store')->getKey()),
        ]);

        abort_unless(
            ($customerId !== null && (int) $this->checkout->customer_id === (int) $customerId)
                || ($customerId === null && in_array($this->checkout->cart_id, $sessionCartIds, true)),
            404,
        );

        $this->email = (string) $this->checkout->email;
        $this->discountCode = (string) ($this->checkout->discount_code ?? '');
        $this->shippingRateId = $this->checkout->shipping_rate_id;
        $this->paymentMethod = (string) ($this->checkout->payment_method ?? PaymentMethod::CreditCard->value);

        if ($this->checkout->shipping_address_json !== null) {
            $this->shippingAddress = array_merge($this->shippingAddress, $this->checkout->shipping_address_json);
        }

        if ($this->checkout->billing_address_json !== null) {
            $this->billingAddress = array_merge($this->billingAddress, $this->checkout->billing_address_json);
            $this->billingSameAsShipping = $this->billingAddress === $this->shippingAddress;
        }

        $this->activeStep = match ($this->checkout->status) {
            CheckoutStatus::Started => 1,
            CheckoutStatus::Addressed => 3,
            CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected, CheckoutStatus::PaymentPending, CheckoutStatus::Completed => 4,
            default => 1,
        };
    }

    public function saveContact(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Enter an email address to receive your order confirmation.',
        ]);

        $this->checkout->update(['email' => $this->email]);
        $this->checkout->refresh();
        $this->message = 'Contact information saved.';
        $this->activeStep = 2;
    }

    public function saveAddress(CheckoutService $checkouts, PricingEngine $pricing): void
    {
        $this->normalizeAddresses();

        $rules = [
            'email' => ['required', 'email', 'max:255'],
            ...$this->addressRules('shippingAddress'),
        ];

        if (! $this->billingSameAsShipping) {
            $rules = [...$rules, ...$this->addressRules('billingAddress')];
        }

        $this->validate($rules);

        $this->checkout->update(['email' => $this->email]);
        $this->checkout = $checkouts->setAddress(
            $this->checkout->refresh(),
            $this->shippingAddress,
            $this->billingAddress,
            $this->billingSameAsShipping,
        );

        if ($this->requiresShipping()) {
            $this->checkout->update([
                'shipping_rate_id' => null,
                'shipping_method_id' => null,
                'status' => CheckoutStatus::Addressed,
            ]);
            $this->checkout = $this->checkout->refresh();
            $pricing->calculate($this->checkout);
            $this->checkout = $this->checkout->refresh();
            $this->shippingRateId = null;
            $this->activeStep = 3;
        } else {
            $this->checkout = $checkouts->setShippingMethod($this->checkout, 0);
            $this->shippingRateId = null;
            $this->activeStep = 4;
        }

        $this->message = 'Address saved.';
    }

    public function selectSavedAddress(int|string|null $addressId): void
    {
        $customerId = auth('customer')->id();

        if ($customerId === null || $addressId === null || $addressId === '') {
            $this->savedAddressId = null;

            return;
        }

        $address = CustomerAddress::query()
            ->whereKey((int) $addressId)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $this->savedAddressId = $address->getKey();
        $this->shippingAddress = array_merge($this->shippingAddress, $address->address_json ?? []);

        if ($this->billingSameAsShipping) {
            $this->billingAddress = $this->shippingAddress;
        }
    }

    public function updatedBillingSameAsShipping(bool $same): void
    {
        if ($same) {
            $this->billingAddress = $this->shippingAddress;
        }
    }

    public function chooseShipping(CheckoutService $checkouts): void
    {
        if (! $this->requiresShipping()) {
            $this->checkout = $checkouts->setShippingMethod($this->checkout, 0);
            $this->activeStep = 4;

            return;
        }

        $this->validate(['shippingRateId' => ['required', 'integer']]);

        try {
            $this->checkout = $checkouts->setShippingMethod($this->checkout, $this->shippingRateId);
            $this->message = 'Shipping method saved.';
            $this->activeStep = 4;
        } catch (Throwable $exception) {
            $this->addError('shippingRateId', $exception->getMessage());
        }
    }

    public function applyDiscount(DiscountService $discounts, PricingEngine $pricing): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);

        try {
            $code = trim($this->discountCode);
            $discount = $discounts->validate($code, app('current_store'), $this->checkout->cart);
            $this->checkout->cart->update(['discount_code' => $discount->code]);
            $this->checkout->update(['discount_code' => $discount->code]);
            $this->checkout = $this->checkout->refresh();
            $pricing->calculate($this->checkout);
            $this->checkout = $this->checkout->refresh();
            $this->checkout->load(['cart.lines.variant.product.media', 'shippingRate']);
            $this->discountCode = $discount->code;
            $this->message = 'Discount applied.';
        } catch (InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(PricingEngine $pricing): void
    {
        $this->checkout->cart->update(['discount_code' => null]);
        $this->checkout->update(['discount_code' => null]);
        $this->checkout = $this->checkout->refresh();
        $pricing->calculate($this->checkout);
        $this->checkout = $this->checkout->refresh();
        $this->checkout->load(['cart.lines.variant.product.media', 'shippingRate']);
        $this->discountCode = '';
        $this->message = 'Discount removed.';
        $this->resetValidation('discountCode');
    }

    public function pay(CheckoutService $checkouts, PaymentService $payments): void
    {
        if ($this->processing) {
            return;
        }

        $this->cardNumber = preg_replace('/\D+/', '', $this->cardNumber) ?? '';
        $rules = [
            'email' => ['required', 'email', 'max:255'],
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ];

        if ($this->paymentMethod === PaymentMethod::CreditCard->value) {
            $rules = [
                ...$rules,
                'cardNumber' => ['required', 'digits:16'],
                'cardholderName' => ['required', 'string', 'max:255'],
                'cardExpiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
                'cardCvc' => ['required', 'digits_between:3,4'],
            ];
        }

        $this->validate($rules);

        if ($this->paymentMethod === PaymentMethod::CreditCard->value && ! $this->cardExpiryIsFuture()) {
            $this->addError('cardExpiry', 'Enter a future expiry date in MM/YY format.');

            return;
        }

        if ($this->checkout->shipping_address_json === null) {
            $this->addError('payment', 'Save your shipping address before placing the order.');
            $this->activeStep = 2;

            return;
        }

        if ($this->requiresShipping() && $this->checkout->shipping_rate_id === null) {
            $this->addError('payment', 'Choose a shipping method before placing the order.');
            $this->activeStep = 3;

            return;
        }

        $this->processing = true;
        $this->resetValidation('payment');

        try {
            $this->checkout->update(['email' => $this->email]);
            $this->checkout = $checkouts->selectPaymentMethod($this->checkout->refresh(), $this->paymentMethod);
            $order = $payments->pay($this->checkout, PaymentMethod::from($this->paymentMethod), [
                'card_number' => $this->cardNumber,
                'cardholder_name' => $this->cardholderName,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
            ]);

            if ($order === null) {
                $this->addError('payment', 'Payment declined. Check your details and try again.');

                return;
            }

            $this->redirect(route('checkout.confirmation', ['checkoutId' => $order->checkout_id]), navigate: true);
        } catch (Throwable $exception) {
            $this->addError('payment', $exception->getMessage() ?: 'We could not process your payment. Please try again.');
        } finally {
            $this->processing = false;
        }
    }

    public function setActiveStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->maxAvailableStep()) {
            $this->activeStep = $step;
        }
    }

    public function toggleOrderSummary(): void
    {
        $this->showOrderSummary = ! $this->showOrderSummary;
    }

    public function requiresShipping(): bool
    {
        return $this->checkout->cart->lines->contains(fn ($line): bool => (bool) $line->variant?->requires_shipping);
    }

    public function formatMoney(int|float $amount, ?string $currency = null): string
    {
        return number_format((float) $amount / 100, 2, '.', ',').' '.($currency ?? $this->checkout->cart->currency ?? 'EUR');
    }

    public function render(ShippingCalculator $shipping, PricingEngine $pricing): mixed
    {
        $this->checkout->load(['cart.lines.variant.product.media', 'shippingRate']);
        $rates = $this->checkout->shipping_address_json === null || ! $this->requiresShipping()
            ? collect()
            : $shipping->getAvailableRates(app('current_store'), $this->checkout->shipping_address_json);
        $totals = $this->checkout->totals_json ?? $pricing->calculate($this->checkout)->toArray();
        $savedAddresses = auth('customer')->user()?->addresses()->latest()->get() ?? collect();

        return view('livewire.storefront.checkout.show', compact('rates', 'totals', 'savedAddresses'))->layout('layouts.storefront');
    }

    /** @return array<string, array<int, string>> */
    private function addressRules(string $prefix): array
    {
        return [
            $prefix.'.first_name' => ['required', 'string', 'max:255'],
            $prefix.'.last_name' => ['required', 'string', 'max:255'],
            $prefix.'.address1' => ['required', 'string', 'max:500'],
            $prefix.'.address2' => ['nullable', 'string', 'max:500'],
            $prefix.'.city' => ['required', 'string', 'max:255'],
            $prefix.'.state' => ['required', 'string', 'max:255'],
            $prefix.'.country_code' => ['required', 'string', 'size:2'],
            $prefix.'.postal_code' => ['required', 'string', 'max:20'],
            $prefix.'.phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    private function normalizeAddresses(): void
    {
        $this->shippingAddress['country_code'] = strtoupper(trim($this->shippingAddress['country_code'] ?? ''));
        $this->billingAddress['country_code'] = strtoupper(trim($this->billingAddress['country_code'] ?? ''));
    }

    private function cardExpiryIsFuture(): bool
    {
        [$month, $year] = array_map('intval', explode('/', $this->cardExpiry));

        return Carbon::create(2000 + $year, $month, 1)->endOfMonth()->isFuture();
    }

    private function maxAvailableStep(): int
    {
        return match ($this->checkout->status) {
            CheckoutStatus::Started => $this->checkout->email === $this->email && $this->email !== '' ? 2 : 1,
            CheckoutStatus::Addressed => 3,
            CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected, CheckoutStatus::PaymentPending, CheckoutStatus::Completed => 4,
            default => 1,
        };
    }
}
