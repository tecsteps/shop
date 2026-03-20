<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Exceptions\PaymentFailedException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ShippingCalculator;
use Livewire\Component;

class Show extends Component
{
    public ?int $checkoutId = null;

    public int $currentStep = 1;

    // Step 1: Contact
    public string $email = '';

    // Step 2: Shipping Address
    public string $firstName = '';

    public string $lastName = '';

    public string $address1 = '';

    public string $address2 = '';

    public string $city = '';

    public string $province = '';

    public string $postalCode = '';

    public string $country = 'US';

    public string $phone = '';

    public bool $billingSameAsShipping = true;

    public string $billingFirstName = '';

    public string $billingLastName = '';

    public string $billingAddress1 = '';

    public string $billingAddress2 = '';

    public string $billingCity = '';

    public string $billingProvince = '';

    public string $billingPostalCode = '';

    public string $billingCountry = 'US';

    // Step 3: Shipping Method
    public ?int $selectedShippingRateId = null;

    /** @var array<int, array<string, mixed>> */
    public array $availableShippingRates = [];

    // Step 4: Payment
    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardholderName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    // Discount code
    public string $discountCode = '';

    // State
    public string $paymentError = '';

    public bool $processing = false;

    /** @var array<string, mixed> */
    public array $totals = [];

    /** @var array<string, mixed> */
    public array $cartLines = [];

    public function mount(): void
    {
        $store = app('current_store');
        $cartService = app(CartService::class);
        $customer = auth('customer')->user();

        $cart = $cartService->getOrCreateForSession($store, $customer);

        if ($cart->lines()->count() === 0) {
            $this->redirect(route('storefront.cart'));

            return;
        }

        // Find or create checkout from cart
        $checkout = Checkout::where('cart_id', $cart->id)
            ->whereNotIn('status', [CheckoutStatus::Completed, CheckoutStatus::Expired])
            ->first();

        if (! $checkout) {
            $checkoutService = app(CheckoutService::class);
            $checkout = $checkoutService->createFromCart($cart);
        }

        $this->checkoutId = $checkout->id;

        // Prefill email if customer is logged in
        if ($customer) {
            $this->email = $customer->email;
        }

        // Restore checkout state if partially completed
        if ($checkout->email) {
            $this->email = $checkout->email;
        }

        if ($checkout->shipping_address_json) {
            $addr = $checkout->shipping_address_json;
            $this->firstName = $addr['first_name'] ?? '';
            $this->lastName = $addr['last_name'] ?? '';
            $this->address1 = $addr['address1'] ?? '';
            $this->address2 = $addr['address2'] ?? '';
            $this->city = $addr['city'] ?? '';
            $this->province = $addr['province'] ?? '';
            $this->postalCode = $addr['postal_code'] ?? '';
            $this->country = $addr['country'] ?? 'US';
            $this->phone = $addr['phone'] ?? '';
        }

        // Determine which step the checkout is on
        $this->currentStep = match ($checkout->status) {
            CheckoutStatus::Started => 1,
            CheckoutStatus::Addressed => 3,
            CheckoutStatus::ShippingSelected => 4,
            CheckoutStatus::PaymentPending => 4,
            default => 1,
        };

        if ($checkout->status === CheckoutStatus::Addressed || $checkout->status->value >= CheckoutStatus::ShippingSelected->value) {
            $this->loadShippingRates();
        }

        $this->loadCartData();
        $this->loadTotals();
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'address2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function continueToAddress(): void
    {
        $this->validate(['email' => ['required', 'email', 'max:255']]);
        $this->currentStep = 2;
    }

    public function continueToShipping(): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'address1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postalCode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $shippingAddress = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'phone' => $this->phone,
        ];

        $billingAddress = $this->billingSameAsShipping ? $shippingAddress : [
            'first_name' => $this->billingFirstName,
            'last_name' => $this->billingLastName,
            'address1' => $this->billingAddress1,
            'address2' => $this->billingAddress2,
            'city' => $this->billingCity,
            'province' => $this->billingProvince,
            'postal_code' => $this->billingPostalCode,
            'country' => $this->billingCountry,
        ];

        $checkoutService->setAddress($checkout, [
            'email' => $this->email,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
        ]);

        $this->loadShippingRates();
        $this->loadTotals();
        $this->currentStep = 3;
    }

    public function continueToPayment(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->setShippingMethod($checkout, $this->selectedShippingRateId);

        $this->loadTotals();
        $this->currentStep = 4;
    }

    public function pay(): mixed
    {
        $this->paymentError = '';
        $this->processing = true;

        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);
        $orderService = app(OrderService::class);

        // Select payment method (transitions to PaymentPending)
        if ($checkout->status === CheckoutStatus::ShippingSelected) {
            $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);
            $checkout = $checkout->fresh();
        }

        // Build payment details
        $paymentDetails = [];
        if ($this->paymentMethod === 'credit_card') {
            $this->validate([
                'cardNumber' => ['required', 'string'],
                'cardholderName' => ['required', 'string', 'max:255'],
                'cardExpiry' => ['required', 'string'],
                'cardCvc' => ['required', 'string'],
            ]);

            $paymentDetails = [
                'card_number' => preg_replace('/\s+/', '', $this->cardNumber),
                'cardholder_name' => $this->cardholderName,
                'expiry' => $this->cardExpiry,
                'cvc' => $this->cardCvc,
            ];
        }

        try {
            $order = $orderService->createFromCheckout($checkout, $paymentDetails);
            $this->processing = false;

            return redirect()->route('storefront.checkout.confirmation', $order);
        } catch (PaymentFailedException $e) {
            $this->processing = false;
            $this->paymentError = $e->getMessage();

            return null;
        }
    }

    public function applyDiscount(): void
    {
        if (! $this->discountCode) {
            return;
        }

        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);
        $checkoutService->applyDiscountCode($checkout, $this->discountCode);
        $this->loadTotals();
    }

    public function editStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    protected function loadShippingRates(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutId);
        $store = $checkout->store;
        $address = $checkout->shipping_address_json ?? [];

        $calculator = app(ShippingCalculator::class);
        $rates = $calculator->getAvailableRates($store, $address);

        $cart = $checkout->cart;

        $this->availableShippingRates = $rates->map(function ($rate) use ($calculator, $cart) {
            return [
                'id' => $rate->id,
                'name' => $rate->name,
                'description' => $rate->description ?? '',
                'amount' => $calculator->calculate($rate, $cart) ?? 0,
            ];
        })->toArray();

        // Auto-select first rate if none selected
        if (! $this->selectedShippingRateId && count($this->availableShippingRates) > 0) {
            $this->selectedShippingRateId = $this->availableShippingRates[0]['id'];
        }
    }

    protected function loadTotals(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutId);
        $this->totals = $checkout->totals_json ?? [];
    }

    protected function loadCartData(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutId);
        $cart = $checkout->cart()->with('lines.variant.product')->first();

        $this->cartLines = $cart->lines->map(function ($line) {
            return [
                'id' => $line->id,
                'title' => $line->variant?->product?->title ?? 'Unknown',
                'variant_title' => $line->variant?->title,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price_amount,
                'total' => $line->line_total_amount,
            ];
        })->toArray();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.show')
            ->layout('layouts.storefront.app', ['title' => 'Checkout']);
    }
}
