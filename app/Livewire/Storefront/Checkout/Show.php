<?php

namespace App\Livewire\Storefront\Checkout;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Checkout;
use App\Models\CustomerAddress;
use App\Models\ShippingRate;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Show extends StorefrontComponent
{
    public Checkout $checkout;

    public int $step = 1;

    public string $email = '';

    /** @var array<string, mixed> */
    public array $shipping = [
        'first_name' => '', 'last_name' => '', 'company' => '', 'address1' => '', 'address2' => '',
        'city' => '', 'province' => '', 'province_code' => '', 'postal_code' => '', 'country' => 'DE', 'phone' => '',
    ];

    /** @var array<string, mixed> */
    public array $billing = [
        'first_name' => '', 'last_name' => '', 'company' => '', 'address1' => '', 'address2' => '',
        'city' => '', 'province' => '', 'province_code' => '', 'postal_code' => '', 'country' => 'DE', 'phone' => '',
    ];

    public bool $billingSameAsShipping = true;

    /** @var array<int, array<string, mixed>> */
    public array $shippingRates = [];

    public ?int $shippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardholderName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $discountCode = '';

    public ?string $discountError = null;

    public ?string $paymentError = null;

    public bool $summaryOpen = false;

    public bool $requiresShipping = true;

    public function mount(int|string|Checkout $checkoutId): void
    {
        $this->checkout = ($checkoutId instanceof Checkout
            ? Checkout::query()->whereKey($checkoutId->getKey())
            : Checkout::query()->whereKey($checkoutId))
            ->where('store_id', $this->currentStore()->getKey())
            ->with(['cart.lines.variant.product.media', 'cart.lines.variant.optionValues.option'])
            ->firstOrFail();

        $this->authorizeCheckoutAccess();
        abort_if($this->status() === 'completed', 409, 'This checkout is already complete.');
        abort_if($this->status() === 'expired', 410, 'This checkout has expired.');
        abort_if($this->checkout->cart->lines->isEmpty(), 422, 'Your cart is empty.');
        $this->requiresShipping = app(ShippingCalculator::class)->requiresShipping($this->checkout->cart);

        $this->email = (string) ($this->checkout->email ?: Auth::guard('customer')->user()?->email);
        $this->discountCode = (string) ($this->checkout->discount_code ?: session('cart_discount_code', ''));
        $this->paymentMethod = (string) ($this->enumValue($this->checkout->payment_method) ?: 'credit_card');
        $this->shippingRateId = $this->checkout->shipping_method_id ? (int) $this->checkout->shipping_method_id : null;

        $this->fillAddress('shipping', $this->checkout->shipping_address_json);
        $this->fillAddress('billing', $this->checkout->billing_address_json);

        $this->step = match ($this->status()) {
            'addressed' => 3,
            'shipping_selected', 'payment_selected' => 4,
            default => 1,
        };

        if ($this->step >= 3) {
            $this->loadShippingRates();
        }
    }

    public function saveContact(): void
    {
        $this->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $this->email = mb_strtolower($this->email);
        $this->checkout->forceFill(['email' => $this->email])->save();
        if (! $this->requiresShipping) {
            $service = app(CheckoutService::class);
            $service->setAddress($this->checkout, ['email' => $this->email]);
            $this->checkout->refresh();
            $service->setShippingMethod($this->checkout, null);
            $this->checkout->refresh();
            $this->step = 4;
            $this->dispatch('checkout-step-changed', step: 4);

            return;
        }
        $this->step = 2;
        $this->dispatch('checkout-step-changed', step: 2);
    }

    public function useSavedAddress(int $addressId): void
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 403);

        $address = CustomerAddress::query()->where('customer_id', $customer->getAuthIdentifier())->findOrFail($addressId);
        $data = $this->decodeAddress($address->address_json);
        $this->shipping = array_replace($this->shipping, $this->normalizeAddress($data));
    }

    public function saveAddress(): void
    {
        $rules = $this->addressRules('shipping');
        if (! $this->billingSameAsShipping) {
            $rules = [...$rules, ...$this->addressRules('billing')];
        }

        $this->validate($rules, [], $this->addressAttributes());

        $payload = [
            'email' => $this->email,
            'shipping_address' => $this->serviceAddress($this->shipping),
            'billing_address' => $this->serviceAddress($this->billingSameAsShipping ? $this->shipping : $this->billing),
        ];

        try {
            app(CheckoutService::class)->setAddress($this->checkout, $payload);
            $this->checkout->refresh();
            $this->loadShippingRates();
            $this->step = 3;
            $this->dispatch('checkout-step-changed', step: 3);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('shipping.address1', 'We could not verify this address. Please review it and try again.');
        }
    }

    public function chooseShipping(): void
    {
        if (! $this->requiresShipping) {
            app(CheckoutService::class)->setShippingMethod($this->checkout, null);
            $this->checkout->refresh();
            $this->step = 4;
            $this->dispatch('checkout-step-changed', step: 4);

            return;
        }

        if ($this->shippingRates === []) {
            $this->addError('shippingRateId', 'No shipping methods are available for this address.');

            return;
        }

        $this->validate(['shippingRateId' => ['required', 'integer', Rule::in(array_column($this->shippingRates, 'id'))]]);

        try {
            app(CheckoutService::class)->setShippingMethod($this->checkout, (int) $this->shippingRateId);
            $this->checkout->refresh();
            $this->step = 4;
            $this->dispatch('checkout-step-changed', step: 4);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('shippingRateId', 'That shipping method is no longer available. Please choose another.');
            $this->loadShippingRates();
        }
    }

    public function applyDiscount(): void
    {
        $this->validate(['discountCode' => ['required', 'string', 'max:100']]);

        try {
            $discount = app(DiscountService::class)->validate($this->discountCode, $this->currentStore(), $this->checkout->cart);
            $this->checkout->forceFill(['discount_code' => strtoupper((string) $discount->code)])->save();
            $this->reprice();
            $this->discountCode = strtoupper((string) $discount->code);
            $this->discountError = null;
            $this->dispatch('toast', type: 'success', message: 'Discount applied');
        } catch (\Throwable $exception) {
            $this->discountError = str_contains(strtolower($exception->getMessage()), 'expired') ? 'This code has expired.' : 'Invalid discount code';
        }
    }

    public function removeDiscount(): void
    {
        $this->checkout->forceFill(['discount_code' => null])->save();
        $this->discountCode = '';
        $this->discountError = null;
        $this->reprice();
    }

    public function pay(): mixed
    {
        $rules = ['paymentMethod' => ['required', Rule::in(['credit_card', 'paypal', 'bank_transfer'])]];
        if ($this->paymentMethod === 'credit_card') {
            $rules += [
                'cardNumber' => ['required', 'string', function ($attribute, $value, $fail): void {
                    if (strlen(preg_replace('/\D/', '', $value)) !== 16) {
                        $fail('Enter a valid 16-digit card number.');
                    }
                }],
                'cardholderName' => ['required', 'string', 'max:255'],
                'cardExpiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/', function ($attribute, $value, $fail): void {
                    [$month, $year] = array_map('intval', explode('/', $value));
                    $expiresAt = now()->setYear(2000 + $year)->setMonth($month)->endOfMonth();
                    if ($expiresAt->isPast()) {
                        $fail('Enter a future expiry date.');
                    }
                }],
                'cardCvc' => ['required', 'regex:/^\d{3,4}$/'],
            ];
        }

        $this->validate($rules);
        $key = 'checkout-pay:'.$this->checkout->getKey().':'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->paymentError = 'Too many attempts. Please wait a moment and try again.';

            return null;
        }

        RateLimiter::hit($key, 60);
        $this->paymentError = null;

        try {
            $service = app(CheckoutService::class);
            if ($this->status() !== 'payment_selected' || $this->enumValue($this->checkout->payment_method) !== $this->paymentMethod) {
                $service->selectPaymentMethod($this->checkout, $this->paymentMethod);
                $this->checkout->refresh();
            }

            $order = $service->completeCheckout($this->checkout, [
                'payment_method' => $this->paymentMethod,
                'card_number' => preg_replace('/\D/', '', $this->cardNumber),
                'cardholder_name' => $this->cardholderName,
                'expiry' => $this->cardExpiry,
                'cvc' => $this->cardCvc,
            ]);

            session()->put('last_order_id', $order->getKey());
            session()->put('checkout_access.'.$this->checkout->getKey(), true);
            app(CartService::class)->getOrCreateForSession($this->currentStore(), Auth::guard('customer')->user());
            session()->forget(['cart_discount_code', 'cart_discount_amount', 'cart_free_shipping']);

            return $this->redirect(url('/checkout/'.$this->checkout->getRouteKey().'/confirmation'), navigate: true);
        } catch (\Throwable $exception) {
            report($exception);
            $this->checkout->refresh();
            $message = strtolower($exception->getMessage());
            $this->paymentError = str_contains($message, 'insufficient')
                ? 'Payment declined: insufficient funds.'
                : (str_contains($message, 'declin') ? 'Payment declined: please use another payment method.' : 'Payment could not be processed. Please review your details and try again.');
        }

        return null;
    }

    public function editStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
            $this->dispatch('checkout-step-changed', step: $step);
        }
    }

    public function render(): View
    {
        return $this->storefront(
            view('storefront.checkout.show'),
            'Checkout - '.$this->currentStore()->name,
            'Complete your secure checkout.',
        );
    }

    #[Computed]
    public function totals(): array
    {
        $totals = $this->checkout->totals_json;
        if (is_string($totals)) {
            $totals = json_decode($totals, true);
        }

        $subtotal = (int) $this->checkout->cart->lines->sum('line_subtotal_amount');

        return array_replace([
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => $subtotal,
        ], is_array($totals) ? $totals : []);
    }

    #[Computed]
    public function savedAddresses(): mixed
    {
        $customer = Auth::guard('customer')->user();

        return $customer
            ? CustomerAddress::query()->where('customer_id', $customer->getAuthIdentifier())->orderByDesc('is_default')->get()
            : collect();
    }

    private function loadShippingRates(): void
    {
        try {
            $calculator = app(ShippingCalculator::class);
            $this->requiresShipping = $calculator->requiresShipping($this->checkout->cart);
            if (! $this->requiresShipping) {
                $this->shippingRates = [];

                return;
            }

            $rates = $calculator->getAvailableRates($this->currentStore(), $this->serviceAddress($this->shipping), $this->checkout->cart);
            $this->shippingRates = collect($rates)->map(function (ShippingRate $rate) use ($calculator): array {
                $config = is_string($rate->config_json) ? json_decode($rate->config_json, true) : $rate->config_json;
                try {
                    $amount = $calculator->calculate($rate, $this->checkout->cart);
                } catch (\Throwable) {
                    $amount = (int) data_get($config, 'amount', 0);
                }

                return ['id' => $rate->getKey(), 'name' => $rate->name, 'amount' => (int) $amount, 'description' => data_get($config, 'description', 'Delivery time shown at dispatch')];
            })->values()->all();
        } catch (\Throwable $exception) {
            report($exception);
            $this->shippingRates = [];
        }
    }

    private function reprice(): void
    {
        try {
            $result = app(PricingEngine::class)->calculate($this->checkout);
            $totals = method_exists($result, 'toArray') ? $result->toArray() : (array) $result;
            $this->checkout->forceFill(['totals_json' => $totals])->save();
            unset($this->totals);
        } catch (\Throwable $exception) {
            report($exception);
            $this->checkout->refresh();
        }
    }

    /** @return array<string, array<int, mixed>> */
    private function addressRules(string $prefix): array
    {
        return [
            $prefix.'.first_name' => ['required', 'string', 'max:100'],
            $prefix.'.last_name' => ['required', 'string', 'max:100'],
            $prefix.'.company' => ['nullable', 'string', 'max:150'],
            $prefix.'.address1' => ['required', 'string', 'max:255'],
            $prefix.'.address2' => ['nullable', 'string', 'max:255'],
            $prefix.'.city' => ['required', 'string', 'max:120'],
            $prefix.'.province' => ['nullable', 'string', 'max:120'],
            $prefix.'.postal_code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 -]{1,18}$/'],
            $prefix.'.country' => ['required', 'string', 'size:2'],
            $prefix.'.phone' => ['nullable', 'string', 'max:40'],
        ];
    }

    /** @return array<string, string> */
    private function addressAttributes(): array
    {
        return [
            'shipping.first_name' => 'first name', 'shipping.last_name' => 'last name', 'shipping.address1' => 'address',
            'shipping.postal_code' => 'postal code', 'billing.first_name' => 'billing first name',
            'billing.last_name' => 'billing last name', 'billing.address1' => 'billing address', 'billing.postal_code' => 'billing postal code',
        ];
    }

    /** @return array<string, mixed> */
    private function serviceAddress(array $address): array
    {
        return [...$address, 'zip' => $address['postal_code'] ?? '', 'country_code' => $address['country'] ?? ''];
    }

    private function fillAddress(string $property, mixed $raw): void
    {
        $data = $this->decodeAddress($raw);
        if ($data !== []) {
            $this->{$property} = array_replace($this->{$property}, $this->normalizeAddress($data));
        }
    }

    /** @return array<string, mixed> */
    private function decodeAddress(mixed $raw): array
    {
        if (is_string($raw)) {
            return json_decode($raw, true) ?: [];
        }

        return is_array($raw) ? $raw : [];
    }

    /** @return array<string, mixed> */
    private function normalizeAddress(array $data): array
    {
        $data['postal_code'] = $data['postal_code'] ?? $data['zip'] ?? '';
        $data['country'] = $data['country_code'] ?? $data['country'] ?? 'DE';

        return $data;
    }

    private function authorizeCheckoutAccess(): void
    {
        $customerId = Auth::guard('customer')->id();
        $allowed = session('checkout_access.'.$this->checkout->getKey())
            || ((int) session('cart_id') === (int) $this->checkout->cart_id)
            || ($customerId && (int) $customerId === (int) $this->checkout->customer_id);

        abort_unless($allowed, 403);
    }

    private function status(): string
    {
        return (string) $this->enumValue($this->checkout->status);
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
