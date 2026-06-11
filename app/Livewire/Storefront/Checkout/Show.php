<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\InvalidShippingRateException;
use App\Exceptions\PaymentFailedException;
use App\Livewire\Storefront\Concerns\InteractsWithCart;
use App\Models\Checkout;
use App\Models\CustomerAddress;
use App\Models\ShippingRate;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts::storefront')]
class Show extends Component
{
    use InteractsWithCart;

    public ?int $checkoutId = null;

    /**
     * 1 = contact, 2 = address, 3 = shipping method, 4 = payment method,
     * 5 = payment method selected (pay step ships in Phase 5).
     */
    public int $step = 1;

    public string $email = '';

    /** @var array<string, string> */
    public array $shipping = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'postal_code' => '',
        'country_code' => '',
        'phone' => '',
    ];

    /**
     * Saved-address picker for logged-in customers: an address id, "new"
     * for a blank form, or "" when nothing is selected.
     */
    public string $savedAddressId = '';

    public ?int $selectedRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public ?string $shippingError = null;

    public ?string $paymentError = null;

    /**
     * Human-readable attribute names for validation messages.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'shipping.first_name' => __('first name'),
            'shipping.last_name' => __('last name'),
            'shipping.address1' => __('address'),
            'shipping.city' => __('city'),
            'shipping.postal_code' => __('postal code'),
            'shipping.country_code' => __('country'),
        ];
    }

    public function mount(): void
    {
        $cart = $this->currentCart();

        if ($cart === null || ! $cart->lines()->exists()) {
            $this->redirectRoute('storefront.cart');

            return;
        }

        $checkout = null;

        if (Session::has('checkout_id')) {
            $checkout = Checkout::query()
                ->where('cart_id', $cart->getKey())
                ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
                ->find(Session::get('checkout_id'));
        }

        if ($checkout === null) {
            $checkout = app(CheckoutService::class)->createFromCart(
                $cart,
                $this->currentCustomer(),
                Session::get('cart_discount_code'),
            );

            Session::put('checkout_id', $checkout->getKey());
        }

        $this->checkoutId = $checkout->getKey();
        $this->email = $checkout->email ?? $this->currentCustomer()?->email ?? '';
        $this->shipping = array_merge($this->shipping, array_map(
            fn ($value): string => (string) $value,
            $checkout->shipping_address_json ?? [],
        ));

        $this->prefillFromDefaultAddress($checkout);
        $this->selectedRateId = $checkout->shipping_method_id;
        $this->paymentMethod = $checkout->payment_method ?? 'credit_card';

        $this->step = match ($checkout->status) {
            CheckoutStatus::Started => 1,
            CheckoutStatus::Addressed => 3,
            CheckoutStatus::ShippingSelected => 4,
            default => 5,
        };
    }

    /**
     * Populate the address form when a saved address is picked from the
     * dropdown; "new" resets the form to a blank address (spec 04).
     */
    public function updatedSavedAddressId(string $value): void
    {
        if ($value === '') {
            return;
        }

        $blank = array_fill_keys(array_keys($this->shipping), '');

        if ($value === 'new') {
            $this->shipping = $blank;

            return;
        }

        $address = $this->currentCustomer()?->addresses()->find((int) $value);

        if ($address !== null) {
            $this->shipping = array_merge($blank, $address->toCheckoutAddress());
        }
    }

    public function saveContact(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $this->step = max($this->step, 2);
    }

    public function saveAddress(CheckoutService $checkoutService): void
    {
        $postalCodeRules = ['required', 'string', 'max:32'];

        if (($this->shipping['country_code'] ?? '') === 'DE') {
            $postalCodeRules[] = 'regex:/^\d{5}$/';
        }

        $this->validate(
            [
                'email' => ['required', 'email'],
                'shipping.first_name' => ['required', 'string', 'max:255'],
                'shipping.last_name' => ['required', 'string', 'max:255'],
                'shipping.address1' => ['required', 'string', 'max:255'],
                'shipping.city' => ['required', 'string', 'max:255'],
                'shipping.postal_code' => $postalCodeRules,
                'shipping.country_code' => ['required', 'string', 'size:2'],
            ],
            ['shipping.postal_code.regex' => __('The postal code format is invalid for the selected country.')],
        );

        $checkout = $checkoutService->setAddress($this->checkout(), [
            'email' => $this->email,
            'shipping_address' => array_filter($this->shipping, fn (string $value): bool => $value !== ''),
        ]);

        $this->shippingError = null;
        $this->selectedRateId = null;

        $cart = $this->currentCart();

        if ($cart !== null && ! $cart->requiresShipping()) {
            $checkoutService->setShippingMethod($checkout);
            $this->step = 4;

            return;
        }

        $this->step = 3;
    }

    public function saveShipping(CheckoutService $checkoutService): void
    {
        $this->shippingError = null;

        try {
            $checkoutService->setShippingMethod($this->checkout(), $this->selectedRateId);
        } catch (InvalidShippingRateException) {
            $this->shippingError = __('Please choose one of the available shipping methods.');

            return;
        }

        $this->step = 4;
    }

    /**
     * Charge the mock PSP and create the order; on success redirect to the
     * confirmation page. Declines keep the customer on the payment step.
     * The selected payment method is persisted to the checkout first, so
     * switching the radio buttons right before paying always takes effect.
     */
    public function payNow(CheckoutService $checkoutService): void
    {
        $this->paymentError = null;

        if ($this->paymentMethod === 'credit_card') {
            $this->validate([
                'cardNumber' => ['required', 'string', 'regex:/^[\d ]{12,23}$/'],
                'cardName' => ['required', 'string', 'max:255'],
                'cardExpiry' => ['required', 'string', 'max:7'],
                'cardCvc' => ['required', 'string', 'min:3', 'max:4'],
            ]);
        }

        $checkout = $this->checkout();

        try {
            if ($checkout->status === CheckoutStatus::ShippingSelected) {
                $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);
            } elseif ($checkout->payment_method !== $this->paymentMethod) {
                $checkout->forceFill(['payment_method' => $this->paymentMethod])->save();
            }
        } catch (InsufficientInventoryException) {
            $this->paymentError = __('Some items in your cart are no longer in stock.');

            return;
        }

        try {
            $order = $checkoutService->completeCheckout($this->checkout(), [
                'card_number' => $this->cardNumber,
                'card_name' => $this->cardName,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
            ]);
        } catch (PaymentFailedException $exception) {
            $this->paymentError = __($exception->getMessage());

            return;
        } catch (InvalidCheckoutTransitionException) {
            $this->paymentError = __('This checkout can no longer be completed. Please start over from your cart.');

            return;
        }

        Session::forget(['checkout_id', 'cart_id', 'cart_discount_code']);

        $this->redirectRoute('storefront.checkout.confirmation', ['checkoutId' => $order->checkout_id]);
    }

    public function editStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step && $this->step < 5) {
            $this->step = $step;
        }
    }

    /**
     * Apply a discount code directly to the checkout and recalculate.
     */
    public function applyDiscount(): void
    {
        $this->discountError = null;

        $cart = $this->currentCart();
        $code = trim($this->discountCode);

        if ($cart === null || $code === '') {
            return;
        }

        try {
            $discount = app(DiscountService::class)->validate($code, $this->currentStore(), $cart);
        } catch (InvalidDiscountException $exception) {
            $this->discountError = $exception->getMessage();

            return;
        }

        Session::put('cart_discount_code', $discount->code);

        $checkout = $this->checkout();
        $checkout->forceFill(['discount_code' => $discount->code])->save();

        app(CheckoutService::class)->recalculate($checkout);

        $this->discountCode = '';
    }

    /**
     * Remove the applied discount code and recalculate.
     */
    public function removeDiscount(): void
    {
        Session::forget('cart_discount_code');

        $checkout = $this->checkout();
        $checkout->forceFill(['discount_code' => null])->save();

        app(CheckoutService::class)->recalculate($checkout);
    }

    public function render(): View
    {
        $checkout = $this->checkout();
        $cart = $this->currentCart();
        $totals = $checkout->totals_json ?? [];

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'lines' => $this->cartLineData($cart),
            'currency' => $totals['currency'] ?? $cart?->currency ?? $this->currentStore()->default_currency,
            'totals' => $totals,
            'availableRates' => $this->availableRates($checkout),
            'requiresShipping' => $cart?->requiresShipping() ?? false,
            'savedAddresses' => $this->savedAddresses(),
        ])->title(__('Checkout'));
    }

    protected function checkout(): Checkout
    {
        return Checkout::query()->findOrFail($this->checkoutId);
    }

    /**
     * Prefill the address step from the logged-in customer's default
     * address when the checkout has no address yet (spec 04 section 9).
     */
    protected function prefillFromDefaultAddress(Checkout $checkout): void
    {
        if (($checkout->shipping_address_json ?? []) !== []) {
            return;
        }

        $default = $this->currentCustomer()
            ?->addresses()
            ->where('is_default', true)
            ->first();

        if ($default !== null) {
            $this->shipping = array_merge($this->shipping, $default->toCheckoutAddress());
            $this->savedAddressId = (string) $default->getKey();
        }
    }

    /**
     * The logged-in customer's saved addresses for the address picker.
     *
     * @return list<array{id: int, label: string, summary: string}>
     */
    protected function savedAddresses(): array
    {
        $customer = $this->currentCustomer();

        if ($customer === null) {
            return [];
        }

        return $customer->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn (CustomerAddress $address): array => [
                'id' => $address->getKey(),
                'label' => (string) $address->label,
                'summary' => $address->summaryLine(),
            ])
            ->all();
    }

    /**
     * Available shipping rates for the checkout address with calculated costs.
     *
     * @return list<array{id: int, name: string, amount: int}>
     */
    protected function availableRates(Checkout $checkout): array
    {
        $cart = $this->currentCart();

        if ($cart === null || $checkout->shipping_address_json === null) {
            return [];
        }

        $calculator = app(ShippingCalculator::class);

        return $calculator
            ->getAvailableRates($this->currentStore(), $checkout->shipping_address_json)
            ->map(function (ShippingRate $rate) use ($calculator, $cart): ?array {
                try {
                    return [
                        'id' => $rate->getKey(),
                        'name' => $rate->name,
                        'amount' => $calculator->calculate($rate, $cart),
                    ];
                } catch (RuntimeException) {
                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();
    }
}
