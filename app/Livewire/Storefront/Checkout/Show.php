<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\ShippingCalculator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Checkout')]
class Show extends Component
{
    public string $checkoutId = '';

    public int $currentStep = 1;

    // Step 1 - Contact
    public string $email = '';

    // Step 2 - Shipping Address
    public string $shippingFirstName = '';

    public string $shippingLastName = '';

    public string $shippingAddress1 = '';

    public string $shippingAddress2 = '';

    public string $shippingCity = '';

    public string $shippingProvince = '';

    public string $shippingZip = '';

    public string $shippingCountry = 'DE';

    public string $shippingPhone = '';

    public bool $billingSameAsShipping = true;

    // Step 3 - Shipping Method
    public ?int $selectedShippingRateId = null;

    /** @var list<array{id: int, name: string, amount: int, type: string}> */
    public array $availableRates = [];

    // Step 4 - Payment
    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardName = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $paymentError = '';

    public bool $processing = false;

    public function mount(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutId);

        if ($checkout->email) {
            $this->email = $checkout->email;
        }

        if ($checkout->shipping_address_json) {
            $addr = $checkout->shipping_address_json;
            $this->shippingFirstName = $addr['first_name'] ?? '';
            $this->shippingLastName = $addr['last_name'] ?? '';
            $this->shippingAddress1 = $addr['address1'] ?? '';
            $this->shippingAddress2 = $addr['address2'] ?? '';
            $this->shippingCity = $addr['city'] ?? '';
            $this->shippingProvince = $addr['province'] ?? '';
            $this->shippingZip = $addr['zip'] ?? '';
            $this->shippingCountry = $addr['country'] ?? 'DE';
            $this->shippingPhone = $addr['phone'] ?? '';
        }
    }

    public function completeStep1(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $this->currentStep = 2;
    }

    public function completeStep2(): void
    {
        $this->validate([
            'shippingFirstName' => ['required', 'string', 'max:255'],
            'shippingLastName' => ['required', 'string', 'max:255'],
            'shippingAddress1' => ['required', 'string', 'max:500'],
            'shippingCity' => ['required', 'string', 'max:255'],
            'shippingZip' => ['required', 'string', 'max:20'],
            'shippingCountry' => ['required', 'string', 'size:2'],
        ]);

        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->setAddress($checkout, [
            'email' => $this->email,
            'shipping_address' => [
                'first_name' => $this->shippingFirstName,
                'last_name' => $this->shippingLastName,
                'address1' => $this->shippingAddress1,
                'address2' => $this->shippingAddress2,
                'city' => $this->shippingCity,
                'province' => $this->shippingProvince,
                'zip' => $this->shippingZip,
                'country' => $this->shippingCountry,
                'phone' => $this->shippingPhone,
            ],
        ]);

        // Load available shipping rates
        $store = app('current_store');
        $shippingCalc = app(ShippingCalculator::class);
        $zone = $shippingCalc->getMatchingZone(
            ['country' => $this->shippingCountry, 'province_code' => $this->shippingProvince],
            $store
        );

        if ($zone) {
            $cart = $checkout->cart()->with('lines.variant')->first();
            $this->availableRates = $shippingCalc->getAvailableRates($zone, $cart);
        }

        $this->currentStep = 3;
    }

    public function completeStep3(): void
    {
        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        $checkoutService->setShippingMethod($checkout, $this->selectedShippingRateId);
        $this->currentStep = 4;
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    public function pay(): void
    {
        $this->paymentError = '';
        $this->processing = true;

        $checkout = Checkout::findOrFail($this->checkoutId);
        $checkoutService = app(CheckoutService::class);

        // Select payment method first
        $checkoutService->selectPaymentMethod($checkout, $this->paymentMethod);

        $paymentData = [];
        if ($this->paymentMethod === 'credit_card') {
            $this->validate([
                'cardNumber' => ['required', 'string'],
                'cardName' => ['required', 'string', 'max:255'],
                'cardExpiry' => ['required', 'string'],
                'cardCvc' => ['required', 'string', 'min:3', 'max:4'],
            ]);
            $paymentData = [
                'card_number' => str_replace(' ', '', $this->cardNumber),
                'cardholder_name' => $this->cardName,
                'expiry' => $this->cardExpiry,
                'cvc' => $this->cardCvc,
            ];
        }

        try {
            $order = $checkoutService->completeCheckout($checkout, $paymentData);
            $this->redirect(route('storefront.checkout.confirmation', $this->checkoutId));
        } catch (\Exception $e) {
            $this->paymentError = $e->getMessage();
            $this->processing = false;
        }
    }

    public function render()
    {
        $checkout = Checkout::with(['cart.lines.variant.product.media'])->findOrFail($this->checkoutId);
        $lines = $checkout->cart?->lines ?? collect();
        $subtotal = $lines->sum('line_subtotal_amount');
        $totals = $checkout->totals_json ?? [];

        return view('livewire.storefront.checkout.show', [
            'checkout' => $checkout,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'totals' => $totals,
        ]);
    }
}
