<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidDiscountException;
use App\Exceptions\PaymentFailedException;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Multi-step checkout: contact/address -> shipping method -> payment.
 *
 * Each step delegates to {@see CheckoutService}, which validates the transition,
 * recalculates pricing, and snapshots totals on the checkout. The component
 * holds only view state (form fields + selected ids); all domain rules live in
 * the service. Storefront teammate owns final stepper styling (task #6).
 */
#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public ?int $checkoutId = null;

    public string $email = '';

    /** @var array<string, string> */
    public array $address = [
        'first_name' => '',
        'last_name' => '',
        'address1' => '',
        'address2' => '',
        'city' => '',
        'province_code' => '',
        'country' => '',
        'postal_code' => '',
        'phone' => '',
    ];

    public ?int $shippingRateId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $discountCode = '';

    public ?string $discountError = null;

    /**
     * Start (or resume) a checkout for the current session cart.
     */
    public function mount(): void
    {
        $cart = app(CartService::class)->getOrCreateForSession(
            app('current_store'),
            Auth::guard('customer')->user(),
        );

        if ($cart->lines()->count() === 0) {
            $this->redirectRoute('storefront.cart', navigate: true);

            return;
        }

        $checkout = $cart->checkouts()
            ->whereNotIn('status', [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value])
            ->latest('id')
            ->first()
            ?? app(CheckoutService::class)->startFromCart($cart->load('lines.variant'));

        $this->checkoutId = $checkout->id;
    }

    /**
     * Save contact + shipping address (started -> addressed).
     */
    public function saveAddress(): void
    {
        try {
            app(CheckoutService::class)->setAddress($this->checkout(), [
                'email' => $this->email,
                'shipping_address' => $this->address,
            ]);
        } catch (ValidationException $e) {
            $this->addError('address', $e->getMessage());
        }
    }

    /**
     * Choose a shipping method (addressed -> shipping_selected).
     */
    public function selectShipping(): void
    {
        app(CheckoutService::class)->setShippingMethod($this->checkout(), $this->shippingRateId);
    }

    /**
     * Apply (or clear) a discount code, surfacing a specific reason on failure.
     */
    public function applyDiscount(): void
    {
        $this->discountError = null;
        $checkout = $this->checkout();

        if ($this->discountCode === '') {
            app(CheckoutService::class)->applyDiscountCode($checkout, null);

            return;
        }

        try {
            app(CheckoutService::class)->validateDiscountForCheckout($this->discountCode, $checkout);
            app(CheckoutService::class)->applyDiscountCode($checkout, $this->discountCode);
        } catch (InvalidDiscountException $e) {
            $this->discountError = $e->reason;
        }
    }

    /**
     * Reserve inventory + place the order (shipping_selected -> completed).
     */
    public function pay()
    {
        $checkout = $this->checkout();
        $service = app(CheckoutService::class);

        if ($checkout->status === CheckoutStatus::ShippingSelected) {
            $service->selectPaymentMethod($checkout, PaymentMethod::from($this->paymentMethod));
            $checkout = $checkout->fresh();
        }

        try {
            $order = $service->completeCheckout($checkout, ['card_number' => $this->cardNumber]);
        } catch (PaymentFailedException $e) {
            $this->addError('payment', __('Payment failed: :code', ['code' => $e->errorCode]));

            return;
        }

        $this->dispatch('cart-updated', itemCount: 0, cartId: $checkout->cart_id);

        return $this->redirectRoute('storefront.checkout.confirmation', ['orderId' => $order->id], navigate: true);
    }

    public function render()
    {
        $checkout = $this->checkout()->load('cart.lines.variant');

        $rates = $checkout->shipping_address_json
            ? app(ShippingCalculator::class)->getAvailableRates(app('current_store'), $checkout->shipping_address_json)
            : collect();

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'totals' => $checkout->totals_json ?? [],
            'rates' => $rates,
        ]);
    }

    private function checkout(): Checkout
    {
        return Checkout::query()->findOrFail($this->checkoutId);
    }
}
