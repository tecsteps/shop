<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\CheckoutStateException;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Exceptions\UnavailableShippingRateException;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ShippingCalculator;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public int $checkoutId;

    public string $activeStep = 'address';

    /**
     * @var array<string, string|null>
     */
    public array $shippingAddress = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province' => '',
        'province_code' => '',
        'country' => 'Germany',
        'country_code' => 'DE',
        'postal_code' => '',
        'phone' => '',
    ];

    public ?int $selectedShippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '4242424242424242';

    public string $cardExpiry = '12/28';

    public string $cardCvc = '123';

    public string $cardHolder = 'Jane Doe';

    public string $discountCode = '';

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
        $checkout = $this->checkout();

        if (is_array($checkout->shipping_address_json)) {
            $this->shippingAddress = array_merge($this->shippingAddress, $checkout->shipping_address_json);
        }

        $this->selectedShippingRateId = $checkout->shipping_method_id;
        $this->paymentMethod = $checkout->payment_method?->value ?? PaymentMethod::CreditCard->value;
        $this->discountCode = $checkout->discount_code ?? '';
        $this->activeStep = $this->initialStep($checkout);
    }

    public function showStep(string $step): void
    {
        $checkout = $this->checkout();
        $steps = $this->steps($checkout);

        if (! isset($steps[$step]) || ! $steps[$step]['enabled']) {
            return;
        }

        $this->activeStep = $step;
    }

    public function saveAddress(): void
    {
        $this->validate([
            'shippingAddress.first_name' => ['required', 'string', 'max:255'],
            'shippingAddress.last_name' => ['required', 'string', 'max:255'],
            'shippingAddress.address1' => ['required', 'string', 'max:500'],
            'shippingAddress.city' => ['required', 'string', 'max:255'],
            'shippingAddress.country_code' => ['required', 'string', 'size:2'],
            'shippingAddress.postal_code' => ['required', 'string', 'max:20'],
        ]);

        try {
            app(CheckoutService::class)->setAddress($this->checkout(), [
                'shipping_address' => $this->shippingAddress,
                'use_shipping_as_billing' => true,
            ]);
            $this->activeStep = 'shipping';
        } catch (CheckoutStateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function selectShipping(): void
    {
        $this->validate([
            'selectedShippingRateId' => ['required', 'integer'],
        ]);

        try {
            app(CheckoutService::class)->setShippingMethod($this->checkout(), (int) $this->selectedShippingRateId);
            $this->activeStep = 'payment';
        } catch (CheckoutStateException|UnavailableShippingRateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function applyDiscount(): void
    {
        $this->validate([
            'discountCode' => ['required', 'string', 'max:50'],
        ]);

        try {
            app(CheckoutService::class)->applyDiscount($this->checkout(), $this->discountCode);
        } catch (CheckoutStateException|InvalidDiscountException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function removeDiscount(): void
    {
        try {
            app(CheckoutService::class)->removeDiscount($this->checkout());
            $this->discountCode = '';
        } catch (CheckoutStateException $exception) {
            $this->addError('discountCode', $exception->getMessage());
        }
    }

    public function selectPayment(): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
        ]);

        try {
            app(CheckoutService::class)->selectPaymentMethod(
                $this->checkout(),
                PaymentMethod::from($this->paymentMethod),
            );
            session()->flash('checkout_status', 'Payment method saved.');
        } catch (CheckoutStateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }
    }

    public function pay(): mixed
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:credit_card,paypal,bank_transfer'],
            'cardNumber' => ['required_if:paymentMethod,credit_card', 'nullable', 'regex:/^[0-9 ]{16,23}$/'],
            'cardExpiry' => ['required_if:paymentMethod,credit_card', 'nullable', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cardCvc' => ['required_if:paymentMethod,credit_card', 'nullable', 'digits_between:3,4'],
            'cardHolder' => ['required_if:paymentMethod,credit_card', 'nullable', 'string', 'max:255'],
        ]);

        $checkout = $this->checkout();

        if ($checkout->order !== null) {
            return $this->redirect(route('storefront.checkout.confirmation', $checkout), navigate: true);
        }

        try {
            $method = PaymentMethod::from($this->paymentMethod);

            if ($checkout->status !== CheckoutStatus::PaymentSelected || $checkout->payment_method !== $method) {
                $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, $method);
            }

            app(OrderService::class)->createFromCheckout($checkout, [
                'payment_method' => $this->paymentMethod,
                'card_number' => preg_replace('/\D+/', '', $this->cardNumber) ?? '',
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
                'card_holder' => $this->cardHolder,
            ]);

            return $this->redirect(route('storefront.checkout.confirmation', $checkout), navigate: true);
        } catch (PaymentFailedException $exception) {
            $this->addError('payment', $exception->getMessage());
        } catch (CheckoutStateException $exception) {
            $this->addError('checkout', $exception->getMessage());
        }

        return null;
    }

    public function render(): View
    {
        $checkout = $this->checkout();
        $shippingMethods = $checkout->shipping_address_json
            ? app(ShippingCalculator::class)->getAvailableRateQuotes(app('current_store'), $checkout->cart, $checkout->shipping_address_json)
            : collect();

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'shippingMethods' => $shippingMethods,
            'steps' => $this->steps($checkout),
            'totals' => $checkout->totals_json ?? [],
        ])->layout('storefront.layouts.app', [
            'title' => 'Checkout',
        ]);
    }

    private function checkout(): Checkout
    {
        return Checkout::withoutGlobalScopes()
            ->with('cart.lines.variant.product.media', 'cart.lines.variant.optionValues.option', 'cart.lines.variant.inventoryItem', 'shippingRate', 'order')
            ->where('store_id', app('current_store')->id)
            ->whereKey($this->checkoutId)
            ->firstOrFail();
    }

    private function initialStep(Checkout $checkout): string
    {
        return match ($checkout->status) {
            CheckoutStatus::Started => 'address',
            CheckoutStatus::Addressed => 'shipping',
            default => 'payment',
        };
    }

    /**
     * @return array<string, array{number: int, title: string, enabled: bool, completed: bool, summary: ?string}>
     */
    private function steps(Checkout $checkout): array
    {
        $address = $checkout->shipping_address_json;
        $hasAddress = is_array($address) && filled($address['address1'] ?? null);
        $hasShipping = in_array($checkout->status, [CheckoutStatus::ShippingSelected, CheckoutStatus::PaymentSelected, CheckoutStatus::Completed], true);
        $hasPayment = in_array($checkout->status, [CheckoutStatus::PaymentSelected, CheckoutStatus::Completed], true);

        return [
            'address' => [
                'number' => 1,
                'title' => 'Contact and address',
                'enabled' => true,
                'completed' => $hasAddress,
                'summary' => $hasAddress
                    ? trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')).' · '.($address['address1'] ?? '').', '.($address['postal_code'] ?? '').' '.($address['city'] ?? '')
                    : $checkout->email,
            ],
            'shipping' => [
                'number' => 2,
                'title' => 'Shipping method',
                'enabled' => $hasAddress,
                'completed' => $hasShipping,
                'summary' => $checkout->shippingRate?->name,
            ],
            'payment' => [
                'number' => 3,
                'title' => 'Payment method',
                'enabled' => $hasShipping,
                'completed' => $hasPayment,
                'summary' => $checkout->payment_method?->value ? str($checkout->payment_method->value)->replace('_', ' ')->title()->toString() : null,
            ],
        ];
    }
}
