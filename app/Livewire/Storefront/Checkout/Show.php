<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Storefront\Concerns\DispatchesStorefrontAnalytics;
use App\Models\Checkout as CheckoutModel;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    use DispatchesStorefrontAnalytics;

    public ?int $checkoutId = null;

    public ?int $stepOverride = null;

    public string $email = '';

    /** @var array<string, string> */
    public array $shipping = [
        'first_name' => '', 'last_name' => '', 'address_line_1' => '', 'address_line_2' => '', 'city' => '',
        'state' => '', 'country' => 'DE', 'postal_code' => '', 'phone' => '',
    ];

    /** @var array<string, string> */
    public array $billing = [];

    public bool $billingSameAsShipping = true;

    public ?int $savedAddressId = null;

    public ?int $shippingRateId = null;

    public string $discountCode = '';

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardholderName = '';

    public string $expiry = '';

    public string $cvc = '';

    public ?string $paymentError = null;

    public function mount(?int $checkoutId = null): void
    {
        if (auth('customer')->check()) {
            $this->email = auth('customer')->user()->email;
        }

        $checkoutId ??= session('checkout_id');
        $checkout = $checkoutId
            ? CheckoutModel::withoutGlobalScopes()->where('store_id', app('current_store')->id)->whereIn('status', [
                CheckoutStatus::Started->value,
                CheckoutStatus::Addressed->value,
                CheckoutStatus::ShippingSelected->value,
                CheckoutStatus::PaymentSelected->value,
            ])->find($checkoutId)
            : null;

        if (! $checkout) {
            $cart = app(CartService::class)->findActiveCart(app('current_store'), auth('customer')->user());

            if (! $cart || $cart->lines->isEmpty()) {
                $this->redirectRoute('storefront.cart');

                return;
            }

            $checkout = app(CheckoutService::class)->start($cart, auth('customer')->user());
            session()->put('checkout_id', $checkout->id);
        }

        $this->checkoutId = $checkout->id;
        $this->email = $checkout->email ?? $this->email;
        $this->shipping = [...$this->shipping, ...$this->addressForForm($checkout->shipping_address_json ?? [])];
        $this->billing = $this->addressForForm($checkout->billing_address_json ?? []);
        $this->shippingRateId = $checkout->shipping_method_id;
        $this->discountCode = $checkout->discount_code ?? '';
        $this->paymentMethod = $checkout->payment_method ?? $this->paymentMethod;
    }

    public function getCheckoutStepProperty(): int
    {
        if ($this->stepOverride !== null) {
            return $this->stepOverride;
        }

        $checkout = $this->checkout();

        return match ($checkout->status) {
            CheckoutStatus::PaymentSelected->value => 4,
            CheckoutStatus::ShippingSelected->value => 3,
            CheckoutStatus::Addressed->value => 3,
            default => blank($checkout->email) ? 1 : 2,
        };
    }

    public function getAddressesProperty(): mixed
    {
        $customer = auth('customer')->user();

        return $customer ? $customer->addresses()->orderByDesc('is_default')->get() : collect();
    }

    public function updatedSavedAddressId(?int $addressId): void
    {
        if (! $addressId) {
            return;
        }

        $address = auth('customer')->user()?->addresses()->findOrFail($addressId);

        if ($address) {
            $this->shipping = [...$this->shipping, ...$this->addressForForm($address->address_json)];
        }
    }

    public function continueToAddress(CheckoutService $checkouts): void
    {
        $this->validate(['email' => ['required', 'email', 'max:255']]);
        $checkouts->setContact($this->checkout(), $this->email);
        $this->stepOverride = 2;
    }

    public function continueToShippingMethod(CheckoutService $checkouts): void
    {
        $this->validate([
            'shipping.first_name' => ['required', 'string', 'max:100'],
            'shipping.last_name' => ['required', 'string', 'max:100'],
            'shipping.address_line_1' => ['required', 'string', 'max:255'],
            'shipping.city' => ['required', 'string', 'max:100'],
            'shipping.country' => ['required', 'string', 'size:2'],
            'shipping.postal_code' => ['required', 'string', 'max:24'],
            'billing.first_name' => [$this->billingSameAsShipping ? 'nullable' : 'required', 'string', 'max:100'],
            'billing.last_name' => [$this->billingSameAsShipping ? 'nullable' : 'required', 'string', 'max:100'],
        ]);

        $billing = $this->billingSameAsShipping ? null : $this->billing;
        $checkout = $checkouts->setAddress($this->checkout(), $this->email, $this->shipping, $billing);
        $this->stepOverride = null;

        if (! $this->requiresShipping($checkout)) {
            $this->shippingRateId = null;
            $checkout = $checkouts->selectShippingMethod($checkout, null);
            $checkouts->selectPaymentMethod($checkout, PaymentMethod::tryFrom($this->paymentMethod) ?? PaymentMethod::CreditCard);
        }
    }

    public function continueToPayment(CheckoutService $checkouts): void
    {
        $method = PaymentMethod::tryFrom($this->paymentMethod);

        if (! $method) {
            throw ValidationException::withMessages(['paymentMethod' => 'Choose a supported payment method.']);
        }

        $checkout = $this->checkout();

        if ($checkout->status === CheckoutStatus::Addressed->value) {
            $checkout = $checkouts->selectShippingMethod($checkout, $this->shippingRateId);
        }

        $checkouts->selectPaymentMethod($checkout, $method);
        $this->stepOverride = null;
    }

    public function editStep(int $step, CheckoutService $checkouts): void
    {
        $checkout = $this->checkout();

        if ($step === 1) {
            $checkouts->releaseReservations($checkout);
            $checkout->forceFill(['status' => CheckoutStatus::Started->value, 'shipping_method_id' => null, 'shipping_amount' => 0, 'payment_method' => null])->save();
            $this->stepOverride = 1;
        } elseif ($step === 2 && $checkout->status !== CheckoutStatus::Started->value) {
            $checkouts->releaseReservations($checkout);
            $checkout->forceFill(['status' => CheckoutStatus::Addressed->value, 'shipping_method_id' => null, 'shipping_amount' => 0, 'payment_method' => null])->save();
            $this->stepOverride = 2;
        } elseif ($step === 3 && $checkout->status === CheckoutStatus::PaymentSelected->value) {
            $checkouts->releaseReservations($checkout);
            $checkout->forceFill(['status' => CheckoutStatus::Addressed->value, 'payment_method' => null])->save();
            $this->stepOverride = 3;
        }

        $checkouts->recalculate($checkout->refresh());
    }

    public function applyDiscount(CheckoutService $checkouts): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:64']]);
        $checkouts->applyDiscount($this->checkout(), $this->discountCode);
    }

    public function updatedPaymentMethod(): void
    {
        $this->paymentError = null;
    }

    public function pay(CheckoutService $checkouts): mixed
    {
        if ($this->paymentMethod === PaymentMethod::CreditCard->value) {
            $this->validate([
                'cardNumber' => ['required', 'string', 'min:12', 'max:23'],
                'cardholderName' => ['required', 'string', 'max:255'],
                'expiry' => ['required', 'string', 'max:7'],
                'cvc' => ['required', 'string', 'min:3', 'max:4'],
            ]);
        }

        $method = PaymentMethod::tryFrom($this->paymentMethod);

        if (! $method) {
            throw ValidationException::withMessages(['paymentMethod' => 'Choose a supported payment method.']);
        }

        $checkout = $this->checkout();

        if ($checkout->payment_method !== $method->value) {
            $checkouts->changePaymentMethod($checkout, $method);
        }

        try {
            $order = $checkouts->pay($checkout, [
                'card_number' => $this->cardNumber,
                'cardholder_name' => $this->cardholderName,
                'expiry' => $this->expiry,
                'cvc' => $this->cvc,
            ]);
        } catch (\App\Exceptions\PaymentDeclinedException $exception) {
            $this->paymentError = $exception->getMessage();
            $this->addError('payment', $exception->getMessage());

            return null;
        }

        session()->forget(['cart_id', 'checkout_id']);
        $this->dispatchStorefrontAnalytics('checkout_completed', [
            'order_id' => $order->getKey(),
            'total_amount' => (int) $order->total_amount,
            'currency' => $order->currency,
        ]);

        return $this->redirect(URL::signedRoute('storefront.confirmation', ['checkoutId' => $order->checkout_id]));
    }

    public function render(CheckoutService $checkouts): mixed
    {
        $checkout = $checkouts->recalculate($this->checkout());

        return view('storefront.checkout.index', [
            'checkout' => $checkout,
            'checkoutStep' => $this->getCheckoutStepProperty(),
            'shippingRates' => $this->checkoutStep === 3 ? $checkouts->availableShippingRates($checkout) : collect(),
        ])->layout('storefront.layouts.app');
    }

    private function checkout(): CheckoutModel
    {
        return CheckoutModel::withoutGlobalScopes()
            ->where('store_id', app('current_store')->id)
            ->findOrFail($this->checkoutId);
    }

    /** @param array<string, mixed> $address
     * @return array<string, string>
     */
    private function addressForForm(array $address): array
    {
        return [
            'first_name' => (string) ($address['first_name'] ?? ''),
            'last_name' => (string) ($address['last_name'] ?? ''),
            'address_line_1' => (string) ($address['address_line_1'] ?? $address['address1'] ?? ''),
            'address_line_2' => (string) ($address['address_line_2'] ?? $address['address2'] ?? ''),
            'city' => (string) ($address['city'] ?? ''),
            'state' => (string) ($address['state'] ?? $address['province'] ?? ''),
            'country' => strtoupper((string) ($address['country'] ?? $address['country_code'] ?? 'DE')),
            'postal_code' => (string) ($address['postal_code'] ?? $address['zip'] ?? ''),
            'phone' => (string) ($address['phone'] ?? ''),
        ];
    }

    private function requiresShipping(CheckoutModel $checkout): bool
    {
        $checkout->loadMissing('cart.lines.variant');

        return $checkout->cart->lines->contains(static fn ($line): bool => (bool) $line->variant?->requires_shipping);
    }
}
