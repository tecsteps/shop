<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\UnserviceableShippingAddressException;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\ShippingRate;
use App\Models\Store;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $storeId;

    #[Locked]
    public ?int $checkoutId = null;

    public string $step = 'address';

    public string $email = '';

    /**
     * @var array<string, string>
     */
    public array $shippingAddress = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province_code' => '',
        'country' => 'DE',
        'postal_code' => '',
    ];

    /**
     * @var array<string, string>
     */
    public array $billingAddress = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province_code' => '',
        'country' => 'DE',
        'postal_code' => '',
    ];

    public bool $billingSame = true;

    public ?int $selectedShippingRateId = null;

    public string $discountCode = '';

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public function mount(?Checkout $checkout = null): void
    {
        $this->storeId = $this->store()->getKey();
        $this->email = $this->customer()?->email ?? '';

        if ($checkout instanceof Checkout) {
            $this->mountCheckout($checkout);
        }

        $this->fillFromCheckout($this->checkout());
    }

    public function saveAddress(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'shippingAddress.first_name' => ['required', 'string'],
            'shippingAddress.last_name' => ['required', 'string'],
            'shippingAddress.address1' => ['required', 'string'],
            'shippingAddress.city' => ['required', 'string'],
            'shippingAddress.country' => ['required', 'string', 'size:2'],
            'shippingAddress.postal_code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (strtoupper($this->shippingAddress['country']) === 'DE' && preg_match('/^\d{5}$/', (string) $value) !== 1) {
                        $fail('The postal code format is invalid.');
                    }
                },
            ],
            'billingSame' => ['boolean'],
        ]);

        $checkout = $this->checkout();

        if (! $checkout instanceof Checkout) {
            return;
        }

        try {
            $checkout = app(CheckoutService::class)->setAddress($checkout, [
                'email' => $this->email,
                'shipping_address' => $this->shippingAddress,
                'billing_address' => $this->billingSame ? $this->shippingAddress : $this->billingAddress,
            ]);

            $this->selectedShippingRateId = null;

            if (! $this->requiresShipping()) {
                app(CheckoutService::class)->setShippingMethod($checkout, null);
                $this->step = 'payment';

                return;
            }

            $this->step = 'shipping';
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages([
                'email' => $exception->getMessage(),
            ]);
        }
    }

    public function selectShippingMethod(): void
    {
        $checkout = $this->checkout();

        if (! $checkout instanceof Checkout) {
            return;
        }

        if ($this->requiresShipping()) {
            $this->validate([
                'selectedShippingRateId' => ['required', 'integer'],
            ]);
        }

        try {
            app(CheckoutService::class)->setShippingMethod($checkout, $this->selectedShippingRateId);
            $this->step = 'payment';
        } catch (InvalidCheckoutTransitionException|UnserviceableShippingAddressException $exception) {
            throw ValidationException::withMessages([
                'selectedShippingRateId' => $exception->getMessage(),
            ]);
        }
    }

    public function applyDiscount(): void
    {
        $checkout = $this->checkout();

        if (! $checkout instanceof Checkout) {
            return;
        }

        $checkout->forceFill([
            'discount_code' => trim($this->discountCode) !== '' ? trim($this->discountCode) : null,
        ])->save();

        try {
            app(PricingEngine::class)->calculate($checkout);
            $this->resetErrorBag('discountCode');
        } catch (InvalidDiscountException $exception) {
            $checkout->forceFill(['discount_code' => null])->save();
            $this->discountCode = '';
            app(PricingEngine::class)->calculate($checkout);

            throw ValidationException::withMessages([
                'discountCode' => $exception->getMessage(),
            ]);
        }
    }

    public function selectPaymentMethod(): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ]);

        $checkout = $this->checkout();

        if (! $checkout instanceof Checkout) {
            return;
        }

        try {
            app(CheckoutService::class)->selectPaymentMethod($checkout, $this->paymentMethod);
            $this->step = 'reserved';
        } catch (InvalidCheckoutTransitionException $exception) {
            throw ValidationException::withMessages([
                'paymentMethod' => $exception->getMessage(),
            ]);
        }
    }

    public function placeOrder(): void
    {
        $rules = [
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ];

        if ($this->paymentMethod === 'credit_card') {
            $rules = [
                ...$rules,
                'cardNumber' => ['required', 'string'],
                'cardName' => ['nullable', 'string'],
                'cardExpiry' => ['nullable', 'string'],
                'cardCvc' => ['nullable', 'string'],
            ];
        }

        $this->validate($rules);

        $checkout = $this->checkout();

        if (! $checkout instanceof Checkout) {
            return;
        }

        try {
            if ($checkout->status !== CheckoutStatus::PaymentSelected) {
                $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, $this->paymentMethod);
            }

            $order = app(CheckoutService::class)->completeCheckout($checkout, [
                'card_number' => $this->cardNumber,
                'cardholder_name' => $this->cardName,
                'expiry' => $this->cardExpiry,
                'cvc' => $this->cardCvc,
            ]);

            session([
                'last_order_id' => $order->getKey(),
            ]);
            session()->forget(['cart_id', 'cart_discount_code']);

            $this->redirectRoute('checkout.confirmation', ['checkout' => $checkout->getKey()], navigate: true);
        } catch (InvalidCheckoutTransitionException|PaymentFailedException $exception) {
            $this->step = 'payment';

            throw ValidationException::withMessages([
                $this->paymentMethod === 'credit_card' ? 'cardNumber' : 'paymentMethod' => $exception->getMessage(),
            ]);
        }
    }

    public function store(): Store
    {
        if (isset($this->storeId)) {
            $store = Store::query()->findOrFail($this->storeId);
            app()->instance('current_store', $store);

            return $store;
        }

        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    public function cart(): ?Cart
    {
        if ($this->checkoutId !== null) {
            $checkout = Checkout::withoutGlobalScopes()
                ->with('cart')
                ->where('store_id', $this->storeId)
                ->whereKey($this->checkoutId)
                ->first();

            if ($checkout instanceof Checkout) {
                return $checkout->cart?->load([
                    'lines.variant.product',
                    'lines.variant.optionValues.option',
                ]);
            }
        }

        $cart = app(CartService::class)->currentForSession($this->store(), $this->customer());

        return $cart?->load([
            'lines.variant.product',
            'lines.variant.optionValues.option',
        ]);
    }

    public function checkout(): ?Checkout
    {
        if ($this->checkoutId !== null) {
            $checkout = Checkout::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->whereKey($this->checkoutId)
                ->first();

            if (! $checkout instanceof Checkout) {
                return null;
            }

            $this->authorizeCheckoutAccess($checkout);

            return $this->syncSessionDiscount($checkout)
                ->load(['cart.lines.variant.product', 'cart.lines.variant.optionValues.option']);
        }

        $cart = $this->cart();

        if (! $cart instanceof Cart || $cart->lines->isEmpty()) {
            return null;
        }

        $checkout = Checkout::withoutGlobalScopes()
            ->where('cart_id', $cart->getKey())
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->latest('id')
            ->first();

        if (! $checkout instanceof Checkout) {
            $checkout = app(CheckoutService::class)->createFromCart($cart, $this->customer());
        }

        $this->checkoutId = $checkout->getKey();
        $checkout = $this->syncSessionDiscount($checkout);

        return $checkout->load(['cart.lines.variant.product', 'cart.lines.variant.optionValues.option']);
    }

    /**
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        return $this->cart()?->lines->sortBy('id')->values() ?? collect();
    }

    /**
     * @return Collection<int, ShippingRate>
     */
    public function availableRates(): Collection
    {
        if ($this->shippingAddress['country'] === '') {
            return collect();
        }

        return app(ShippingCalculator::class)->getAvailableRates($this->store(), $this->shippingAddress);
    }

    public function requiresShipping(): bool
    {
        $cart = $this->cart();

        return $cart instanceof Cart && app(ShippingCalculator::class)->requiresShipping($cart);
    }

    /**
     * @return array<int, int>
     */
    public function shippingRateAmounts(): array
    {
        $cart = $this->cart();

        if (! $cart instanceof Cart) {
            return [];
        }

        return $this->availableRates()
            ->mapWithKeys(fn (ShippingRate $rate): array => [
                $rate->getKey() => app(ShippingCalculator::class)->calculate($rate, $cart) ?? 0,
            ])
            ->all();
    }

    public function lineCount(): int
    {
        return $this->lines()->sum('quantity');
    }

    public function subtotal(): int
    {
        return $this->lines()->sum('line_subtotal_amount');
    }

    /**
     * @return array<string, mixed>
     */
    public function totals(): array
    {
        $cart = $this->cart();

        return $this->checkout()?->totals_json ?? [
            'subtotal' => $this->subtotal(),
            'discount' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => $this->subtotal(),
            'currency' => $cart?->currency ?? $this->store()->default_currency,
        ];
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.show', [
            'cart' => $this->cart(),
            'checkout' => $this->checkout(),
            'lines' => $this->lines(),
            'lineCount' => $this->lineCount(),
            'rates' => $this->availableRates(),
            'rateAmounts' => $this->shippingRateAmounts(),
            'requiresShipping' => $this->requiresShipping(),
            'totals' => $this->totals(),
        ])->layout('layouts.storefront', [
            'title' => 'Checkout',
        ]);
    }

    private function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer ? $customer : null;
    }

    private function mountCheckout(Checkout $checkout): void
    {
        $checkout = Checkout::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey($checkout->getKey())
            ->first();

        abort_unless($checkout instanceof Checkout, 404);

        $this->authorizeCheckoutAccess($checkout);

        $this->checkoutId = $checkout->getKey();
    }

    private function authorizeCheckoutAccess(Checkout $checkout): void
    {
        $customer = $this->customer();
        $sessionCartId = session('cart_id');
        $isCustomerCheckout = $customer instanceof Customer && $checkout->customer_id === $customer->getKey();
        $isSessionCheckout = $sessionCartId !== null && (int) $sessionCartId === (int) $checkout->cart_id;

        abort_unless($isCustomerCheckout || $isSessionCheckout, 404);
    }

    private function fillFromCheckout(?Checkout $checkout): void
    {
        if (! $checkout instanceof Checkout) {
            return;
        }

        $this->email = $checkout->email ?: $this->email;
        $this->shippingAddress = array_replace($this->shippingAddress, $checkout->shipping_address_json ?? []);
        $this->billingAddress = array_replace($this->billingAddress, $checkout->billing_address_json ?? []);
        $this->selectedShippingRateId = $checkout->shipping_method_id;
        $this->discountCode = (string) ($checkout->discount_code ?? '');
        $this->paymentMethod = (string) ($checkout->payment_method ?? $this->paymentMethod);
        $this->step = match ($checkout->status) {
            CheckoutStatus::Started => 'address',
            CheckoutStatus::Addressed => 'shipping',
            CheckoutStatus::ShippingSelected => 'payment',
            CheckoutStatus::PaymentSelected => 'reserved',
            default => 'address',
        };
    }

    private function syncSessionDiscount(Checkout $checkout): Checkout
    {
        $code = trim((string) session('cart_discount_code'));

        if ($code === '' || $checkout->discount_code !== null) {
            return $checkout;
        }

        $checkout->forceFill(['discount_code' => $code])->save();

        try {
            app(PricingEngine::class)->calculate($checkout);
        } catch (InvalidDiscountException) {
            $checkout->forceFill(['discount_code' => null])->save();
            session()->forget('cart_discount_code');
            app(PricingEngine::class)->calculate($checkout);
        }

        return $checkout->refresh();
    }
}
