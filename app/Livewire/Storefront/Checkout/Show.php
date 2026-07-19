<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use App\ValueObjects\Address;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Multi-step checkout page at GET /checkout/{checkoutId} (spec 04 §8).
 *
 * Step 1 (contact + shipping address) runs under the "new" route parameter:
 * submitting it creates the checkout from the session cart and sets the
 * address, then redirects to /checkout/{id} for step 2 (shipping method)
 * and step 3 (payment method + pay via the Mock PSP).
 */
class Show extends Component
{
    public ?int $checkoutDbId = null;

    public bool $expired = false;

    public int $step = 1;

    public string $email = '';

    /**
     * Shipping address form fields (snake_case, matching Address::toArray).
     *
     * @var array<string, string>
     */
    public array $address = [
        'first_name' => '',
        'last_name' => '',
        'company' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'province_code' => '',
        'country' => '',
        'country_code' => '',
        'postal_code' => '',
        'phone' => '',
    ];

    public bool $useShippingAsBilling = true;

    public string $paymentMethod = 'credit_card';

    public bool $paymentSelected = false;

    public string $cardNumber = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $cardHolder = '';

    public ?string $paymentError = null;

    public string $discountCode = '';

    public ?string $discountError = null;

    /**
     * Load an existing checkout, or start fresh for "new".
     */
    public function mount(string $checkoutId): void
    {
        if ($checkoutId === 'new') {
            $cart = app(CartService::class)->findForSession(app('current_store'));

            if ($cart === null || $cart->lines->isEmpty()) {
                $this->redirectRoute('storefront.cart.show');

                return;
            }

            return;
        }

        $checkout = Checkout::find((int) $checkoutId);

        abort_if($checkout === null, 404);

        if ($checkout->isExpired()) {
            $this->expired = true;

            return;
        }

        $this->checkoutDbId = $checkout->id;
        $this->email = $checkout->email ?? '';

        if (! empty($checkout->shipping_address_json)) {
            $this->address = array_merge(
                $this->address,
                array_filter($checkout->shipping_address_json, fn ($value) => $value !== null),
            );
        }

        $this->step = match ($checkout->status) {
            CheckoutStatus::Started => 1,
            CheckoutStatus::Addressed => 2,
            default => 3,
        };

        if ($checkout->payment_method !== null) {
            $this->paymentMethod = $checkout->payment_method->value;
            $this->paymentSelected = true;
        }
    }

    /**
     * Step 1 submit: create the checkout (when new) and set the address.
     */
    public function submitAddress(): void
    {
        // The form collects the ISO country code only; the address payload
        // carries both representations (spec 02 §2.2).
        $this->address['country'] = $this->address['country_code'];
        $this->address['country_code'] = strtoupper($this->address['country_code']);
        $this->address['country'] = strtoupper($this->address['country']);

        $this->validate($this->addressRules());

        $service = app(CheckoutService::class);

        try {
            if ($this->checkoutDbId === null) {
                $cart = app(CartService::class)->findForSession(app('current_store'));

                if ($cart === null || $cart->lines->isEmpty()) {
                    $this->addError('email', 'Your cart is empty.');

                    return;
                }

                $checkout = $service->createFromCart(
                    $cart,
                    $this->email,
                    auth('customer')->user(),
                    session('discount_code'),
                );
            } else {
                $checkout = Checkout::findOrFail($this->checkoutDbId);
            }

            $service->setAddress($checkout, [
                'email' => $this->email,
                'shipping_address' => $this->address,
                'use_shipping_as_billing' => $this->useShippingAsBilling,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError(str_replace('shipping_address', 'address', $key), $messages[0]);
            }

            return;
        }

        session()->forget('discount_code');

        $this->redirectRoute('storefront.checkout.show', ['checkoutId' => $checkout->id]);
    }

    /**
     * Step 2: pick a shipping method and advance to payment.
     */
    public function selectShipping(int $rateId): void
    {
        $checkout = Checkout::findOrFail($this->checkoutDbId);

        try {
            app(CheckoutService::class)->setShippingMethod($checkout, $rateId);
        } catch (ValidationException) {
            $this->addError('shippingMethodId', 'The selected shipping method is not available for your address.');

            return;
        }

        $this->step = 3;
    }

    /**
     * Step 2 shortcut for carts without shippable items.
     */
    public function continueWithoutShipping(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutDbId);

        app(CheckoutService::class)->setShippingMethod($checkout, null);

        $this->step = 3;
    }

    /**
     * Step 3: record the payment method (reserves inventory).
     */
    public function selectPayment(): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ]);

        $checkout = Checkout::findOrFail($this->checkoutDbId);

        app(CheckoutService::class)->selectPaymentMethod($checkout, $this->paymentMethod);

        $this->paymentSelected = true;
    }

    /**
     * Step 3 submit: charge the selected payment method and create the order
     * (spec 04 §8.2). On decline the customer stays on the payment step and
     * sees the error; on success they are redirected to the confirmation.
     */
    public function pay(): void
    {
        $this->paymentError = null;

        $checkout = Checkout::findOrFail($this->checkoutDbId);
        $method = $checkout->payment_method?->value ?? $this->paymentMethod;

        $rules = [];

        if ($method === 'credit_card') {
            $rules = [
                'cardNumber' => ['required', 'string', 'max:25'],
                'cardExpiry' => ['required', 'string', 'max:7'],
                'cardCvc' => ['required', 'string', 'max:4'],
                'cardHolder' => ['required', 'string', 'max:255'],
            ];
        }

        $this->validate($rules);

        try {
            app(CheckoutService::class)->completeCheckout($checkout, [
                'payment_method' => $method,
                'card_number' => $this->cardNumber,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
                'card_holder' => $this->cardHolder,
            ]);
        } catch (PaymentFailedException $exception) {
            $this->paymentError = 'Payment declined: '.$exception->getMessage();

            return;
        } catch (InsufficientInventoryException) {
            $this->paymentError = 'Some items in your order are no longer available.';

            return;
        }

        $this->redirectRoute('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]);
    }

    /**
     * Apply a discount code to the checkout.
     */
    public function applyDiscount(): void
    {
        $this->discountError = null;
        $code = trim($this->discountCode);

        if ($this->checkoutDbId === null || $code === '') {
            return;
        }

        $result = app(CheckoutService::class)->applyDiscount(
            Checkout::findOrFail($this->checkoutDbId),
            $code,
        );

        if (! $result->valid) {
            $this->discountError = $result->errorMessage;

            return;
        }

        $this->discountCode = '';
    }

    /**
     * Remove the discount code from the checkout.
     */
    public function removeDiscount(): void
    {
        if ($this->checkoutDbId === null) {
            return;
        }

        app(CheckoutService::class)->removeDiscount(Checkout::findOrFail($this->checkoutDbId));
    }

    /**
     * Validation rules for the step 1 form (mirrors SetCheckoutAddressRequest).
     *
     * @return array<string, array<int, string>>
     */
    private function addressRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'address.first_name' => ['required', 'string', 'max:255'],
            'address.last_name' => ['required', 'string', 'max:255'],
            'address.company' => ['nullable', 'string', 'max:255'],
            'address.address1' => ['required', 'string', 'max:500'],
            'address.address2' => ['nullable', 'string', 'max:500'],
            'address.city' => ['required', 'string', 'max:255'],
            'address.province' => ['nullable', 'string', 'max:255'],
            'address.province_code' => ['nullable', 'string', 'max:10'],
            'address.country' => ['required', 'string', 'max:255'],
            'address.country_code' => ['required', 'string', 'size:2', 'alpha'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.phone' => ['nullable', 'string', 'max:50'],
            'useShippingAsBilling' => ['boolean'],
        ];
    }

    /**
     * Render the checkout page.
     */
    public function render(): View
    {
        $checkout = $this->checkoutDbId !== null
            ? Checkout::with(['cart.lines.variant.product.media', 'cart.lines.variant.optionValues.option'])->find($this->checkoutDbId)
            : null;

        $rates = collect();

        if ($checkout !== null && $checkout->status === CheckoutStatus::Addressed && $checkout->requiresShipping()) {
            $rates = app(ShippingCalculator::class)->getAvailableRates(
                $checkout->store,
                Address::fromArray($checkout->shipping_address_json),
                $checkout->cart,
            );
        }

        // Preview cart for the "new" step (before the checkout exists).
        $previewCart = $checkout === null
            ? app(CartService::class)->findForSession(app('current_store'))
            : null;

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'previewCart' => $previewCart,
            'rates' => $rates,
        ])
            ->layout('storefront.layouts.app')
            ->title('Checkout - '.app('current_store')->name);
    }
}
