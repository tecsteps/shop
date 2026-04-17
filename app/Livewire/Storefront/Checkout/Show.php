<?php

namespace App\Livewire\Storefront\Checkout;

use App\Exceptions\PaymentFailedException;
use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Checkout;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use App\Support\CartSession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use EnsuresStore;

    public string $email = '';

    /** @var array<string, mixed> */
    public array $shippingAddress = [
        'first_name' => '',
        'last_name' => '',
        'line1' => '',
        'line2' => '',
        'city' => '',
        'postal_code' => '',
        'country' => 'DE',
        'province_code' => null,
    ];

    /** @var array<string, mixed> */
    public array $billingAddress = [
        'first_name' => '',
        'last_name' => '',
        'line1' => '',
        'line2' => '',
        'city' => '',
        'postal_code' => '',
        'country' => 'DE',
        'province_code' => null,
    ];

    public bool $billingSameAsShipping = true;

    public ?int $shippingMethodId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '4242424242424242';

    public string $cardExpiry = '12/30';

    public string $cardCvc = '123';

    public int $step = 1;

    public function mount(): void
    {
        $this->ensureCurrentStore();

        $cart = CartSession::current();
        if ($cart === null || $cart->lines()->count() === 0) {
            $this->redirect(route('storefront.cart.show'), navigate: false);

            return;
        }

        $customer = Auth::guard('customer')->user();
        if ($customer !== null && $this->email === '') {
            $this->email = (string) $customer->email;
        }
    }

    public function continueToShipping(): void
    {
        $this->validate([
            'email' => 'required|email',
            'shippingAddress.first_name' => 'required|string|max:100',
            'shippingAddress.last_name' => 'required|string|max:100',
            'shippingAddress.line1' => 'required|string|max:255',
            'shippingAddress.city' => 'required|string|max:120',
            'shippingAddress.postal_code' => 'required|string|max:30',
            'shippingAddress.country' => 'required|string|size:2',
        ]);

        $billing = $this->billingSameAsShipping ? $this->shippingAddress : $this->billingAddress;

        $checkout = $this->getOrCreateCheckout();
        app(CheckoutService::class)->setAddress($checkout, [
            'email' => $this->email,
            'shipping_address' => $this->shippingAddressPayload(),
            'billing_address' => $this->addressToPayload($billing),
        ]);

        $this->step = 2;
    }

    public function continueToPayment(): void
    {
        $this->validate([
            'shippingMethodId' => 'required|integer',
        ]);

        $checkout = $this->getOrCreateCheckout();
        app(CheckoutService::class)->setShippingMethod($checkout, (int) $this->shippingMethodId);

        $this->step = 3;
    }

    public function backToAddress(): void
    {
        $this->step = 1;
    }

    public function backToShipping(): void
    {
        $this->step = 2;
    }

    public function placeOrder(): void
    {
        $this->validate([
            'paymentMethod' => 'required|in:credit_card,paypal,bank_transfer',
        ]);

        $checkout = $this->getOrCreateCheckout();
        $service = app(CheckoutService::class);

        $service->selectPaymentMethod($checkout, $this->paymentMethod);
        $checkout->refresh();

        try {
            $service->complete($checkout, [
                'card_number' => $this->cardNumber,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
            ]);
        } catch (PaymentFailedException $exception) {
            $this->addError('payment', $exception->getMessage());

            return;
        }

        $storeId = (int) $checkout->store_id;
        $order = Order::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->latest('id')
            ->first();

        CartSession::clear();

        if ($order !== null) {
            $this->redirect(
                route('storefront.checkout.confirmation', ['order_number' => ltrim((string) $order->order_number, '#')]),
                navigate: false
            );
        }
    }

    public function render(): View
    {
        $store = $this->ensureCurrentStore();

        $cart = CartSession::current();
        $cart?->load('lines.variant.product');

        $shippingRates = collect();
        if ($this->step >= 2 && $cart !== null) {
            $shippingRates = app(ShippingCalculator::class)->getAvailableRates(
                $store,
                $this->shippingAddressPayload()
            );
        }

        $totals = $this->computeTotals($cart, $shippingRates);

        return view('livewire.storefront.checkout.show', [
            'cart' => $cart,
            'shippingRates' => $shippingRates,
            'totals' => $totals,
        ]);
    }

    private function getOrCreateCheckout(): Checkout
    {
        $cart = CartSession::current();

        if ($cart === null) {
            $this->redirect(route('storefront.cart.show'), navigate: false);
            abort(404);
        }

        $existing = $cart->checkouts()->latest('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        return app(CheckoutService::class)->start($cart);
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingAddressPayload(): array
    {
        return $this->addressToPayload($this->shippingAddress);
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    private function addressToPayload(array $address): array
    {
        return [
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'address1' => $address['line1'] ?? '',
            'address2' => $address['line2'] ?? '',
            'city' => $address['city'] ?? '',
            'postal_code' => $address['postal_code'] ?? '',
            'country' => $address['country'] ?? '',
            'province_code' => $address['province_code'] ?? null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\ShippingRate>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ShippingRate>  $rates
     * @return array<string, int>
     */
    private function computeTotals(?\App\Models\Cart $cart, $rates): array
    {
        $subtotal = 0;
        if ($cart !== null) {
            foreach ($cart->lines as $line) {
                $subtotal += (int) $line->line_subtotal_amount;
            }
        }

        $shipping = 0;
        if ($this->shippingMethodId !== null && $cart !== null) {
            $rate = $rates->firstWhere('id', $this->shippingMethodId);
            if ($rate !== null) {
                $shipping = app(ShippingCalculator::class)->calculate($rate, $cart);
            }
        }

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
        ];
    }
}
