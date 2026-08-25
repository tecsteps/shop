<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Exceptions\InsufficientInventoryException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Exceptions\PaymentFailedException;
use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Checkout;
use App\Services\CheckoutService;
use App\Services\PricingEngine;
use App\Services\ShippingCalculator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use InteractsWithStore;

    public int $checkoutId;

    public string $email = '';

    /** @var array<string, mixed> */
    public array $shipping = [];

    /** @var array<string, mixed> */
    public array $billing = [];

    public bool $billingSameAsShipping = true;

    public ?int $shippingMethodId = null;

    public string $paymentMethod = 'credit_card';

    public string $cardNumber = '';

    public string $cardHolder = '';

    public string $cardExpiry = '';

    public string $cardCvc = '';

    public string $discountCode = '';

    public ?string $discountError = null;

    public string $payError = '';

    public bool $expired = false;

    // UI step state (1 = contact, 2 = address, 3 = shipping method, 4 = payment)
    public int $currentStep = 1;

    public bool $step1Complete = false;

    public bool $step2Complete = false;

    public bool $step3Complete = false;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;

        $checkout = $this->resolveCheckout($checkoutId);

        if ($checkout->status === CheckoutStatus::Completed->value) {
            $this->redirect(route('storefront.checkout.confirmation', ['checkoutId' => $checkout->id]));

            return;
        }

        if ($checkout->status === CheckoutStatus::Expired->value) {
            $this->expired = true;

            return;
        }

        $this->email = (string) ($checkout->email ?? '');
        $this->shipping = $checkout->shipping_address_json ?? [];
        $this->billing = $checkout->billing_address_json ?? [];
        $this->billingSameAsShipping = $this->billing === [] || $this->billing == $this->shipping;
        $this->shippingMethodId = $checkout->shipping_method_id;
        $this->paymentMethod = $checkout->payment_method ?: 'credit_card';

        match ($checkout->status) {
            CheckoutStatus::Started->value => $this->currentStep = 1,
            CheckoutStatus::Addressed->value => $this->markStepsComplete(2),
            CheckoutStatus::ShippingSelected->value => $this->markStepsComplete(3),
            CheckoutStatus::PaymentSelected->value => $this->markStepsComplete(3),
            default => null,
        };
    }

    public function continueFromContact(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $this->step1Complete = true;
        $this->currentStep = 2;
    }

    public function editStep(int $step): void
    {
        $this->payError = '';
        $this->currentStep = $step;
    }

    public function continueFromAddress(): void
    {
        $this->shipping['country'] = $this->countryName((string) ($this->shipping['country_code'] ?? ''));
        $this->billing['country'] = $this->countryName((string) ($this->billing['country_code'] ?? ''));

        $this->validateAddress('shipping');

        $checkout = $this->checkout;

        if ($this->billingSameAsShipping) {
            $billing = $this->shipping;
        } else {
            $this->validateAddress('billing');
            $billing = $this->billing;
        }

        try {
            app(CheckoutService::class)->setAddress($checkout, [
                'email' => $this->email,
                'shipping_address' => $this->shipping,
                'billing_address' => $billing,
            ]);
        } catch (InvalidCheckoutTransitionException) {
            $this->payError = 'This checkout can no longer be updated.';

            return;
        }

        $this->step1Complete = true;
        $this->step2Complete = true;
        $this->currentStep = 3;
    }

    public function continueFromShipping(): void
    {
        $this->validate([
            'shippingMethodId' => ['required', 'integer'],
        ]);

        try {
            app(CheckoutService::class)->setShippingMethod($this->checkout, $this->shippingMethodId);
        } catch (InvalidCheckoutTransitionException|\InvalidArgumentException) {
            $this->payError = 'The selected shipping method is not available.';

            return;
        }

        $this->step1Complete = true;
        $this->step2Complete = true;
        $this->step3Complete = true;
        $this->currentStep = 4;
    }

    public function pay(): void
    {
        $this->payError = '';

        if ($this->paymentMethod === 'credit_card') {
            $this->validate([
                'cardNumber' => ['required', 'digits:16'],
                'cardHolder' => ['required', 'string', 'max:255'],
                'cardExpiry' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
                'cardCvc' => ['required', 'digits_between:3,4'],
            ]);

            if (! $this->isExpiryValid()) {
                $this->payError = 'Your card has expired. Please check the expiry date.';

                return;
            }
        }

        $checkout = $this->checkout;

        try {
            if ($checkout->status !== CheckoutStatus::PaymentSelected->value) {
                $checkout = app(CheckoutService::class)->selectPaymentMethod($checkout, $this->paymentMethod);
            }

            app(CheckoutService::class)->completeCheckout($checkout, [
                'payment_method' => $this->paymentMethod,
                'card_number' => $this->cardNumber,
                'card_expiry' => $this->cardExpiry,
                'card_cvc' => $this->cardCvc,
                'card_holder' => $this->cardHolder,
            ]);
        } catch (PaymentFailedException $e) {
            $this->payError = 'Payment declined: '.$e->getMessage();

            return;
        } catch (InvalidCheckoutTransitionException $e) {
            $this->payError = $e->getMessage();

            return;
        } catch (InsufficientInventoryException) {
            $this->payError = 'Some items in your cart are no longer available. Please return to your cart.';

            return;
        }

        $this->redirect(route('storefront.checkout.confirmation', ['checkoutId' => $this->checkoutId]));
    }

    public function applyCheckoutDiscount(): void
    {
        $this->discountError = null;

        $code = trim($this->discountCode);

        if ($code === '') {
            $this->discountError = 'Please enter a discount code.';

            return;
        }

        $checkout = $this->checkout;

        try {
            $checkout->update(['discount_code' => $code]);
            $checkout->update(['totals_json' => app(PricingEngine::class)->calculate($checkout)->toArray()]);
        } catch (\App\Exceptions\InvalidDiscountException $e) {
            $this->discountError = $e->getMessage();

            return;
        }

        $this->discountCode = '';
    }

    public function removeCheckoutDiscount(): void
    {
        $checkout = $this->checkout;
        $checkout->update(['discount_code' => null]);
        $checkout->update(['totals_json' => app(PricingEngine::class)->calculate($checkout)->toArray()]);
    }

    public function loadSavedAddress(int $addressId): void
    {
        $customer = $this->customer();

        if (! $customer) {
            return;
        }

        $address = DB::table('customer_addresses')
            ->where('customer_id', $customer->id)
            ->where('id', $addressId)
            ->first();

        if (! $address) {
            return;
        }

        $this->shipping = array_replace($this->shipping, json_decode((string) $address->address_json, true) ?? []);
    }

    #[Computed]
    public function checkout(): Checkout
    {
        return $this->resolveCheckout($this->checkoutId);
    }

    #[Computed]
    public function shippingMethods(): SupportCollection
    {
        $checkout = $this->checkout;

        if (! $checkout->shipping_address_json) {
            return collect();
        }

        return app(ShippingCalculator::class)
            ->getAvailableRates($this->store(), $checkout->shipping_address_json, $checkout->cart);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function totals(): array
    {
        return $this->checkout->totals_json ?? [];
    }

    #[Computed]
    public function currency(): string
    {
        return $this->totals['currency']
            ?? $this->checkout->cart?->currency
            ?? $this->store()->default_currency;
    }

    /**
     * @return list<array{id: int, label: string|null}>
     */
    #[Computed]
    public function savedAddresses(): array
    {
        $customer = $this->customer();

        if (! $customer) {
            return [];
        }

        return DB::table('customer_addresses')
            ->where('customer_id', $customer->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => $this->addressLabel(json_decode((string) $row->address_json, true) ?? []),
            ])
            ->all();
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function countries(): array
    {
        return [
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'AT', 'name' => 'Austria'],
            ['code' => 'BE', 'name' => 'Belgium'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'DK', 'name' => 'Denmark'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'FI', 'name' => 'Finland'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'IE', 'name' => 'Ireland'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'LU', 'name' => 'Luxembourg'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'PL', 'name' => 'Poland'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'AU', 'name' => 'Australia'],
        ];
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function validateAddress(string $prefix): void
    {
        $this->validate([
            $prefix.'.first_name' => ['required', 'string', 'max:255'],
            $prefix.'.last_name' => ['required', 'string', 'max:255'],
            $prefix.'.address1' => ['required', 'string', 'max:500'],
            $prefix.'.city' => ['required', 'string', 'max:255'],
            $prefix.'.country_code' => ['required', 'string', 'size:2'],
            $prefix.'.postal_code' => ['required', 'string', 'max:20'],
            $prefix.'.country' => ['required', 'string', 'max:255'],
        ]);
    }

    private function countryName(string $code): string
    {
        foreach ($this->countries() as $country) {
            if ($country['code'] === $code) {
                return $country['name'];
            }
        }

        return $code;
    }

    private function isExpiryValid(): bool
    {
        if (! preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $this->cardExpiry, $matches)) {
            return false;
        }

        $month = (int) $matches[1];
        $year = 2000 + (int) $matches[2];
        $now = now();

        return $year > $now->year || ($year === $now->year && $month >= $now->month);
    }

    private function markStepsComplete(int $throughStep): void
    {
        $this->step1Complete = $throughStep >= 1;
        $this->step2Complete = $throughStep >= 2;
        $this->step3Complete = $throughStep >= 3;
        $this->currentStep = $throughStep + 1;
    }

    private function resolveCheckout(int $id): Checkout
    {
        $checkout = Checkout::findOrFail($id);

        $sessionCartId = session('cart_id');
        $customer = Auth::guard('customer')->user();

        $owns = (int) $checkout->cart_id === (int) $sessionCartId
            || ($customer !== null && (int) $customer->id === (int) $checkout->customer_id);

        if (! $owns) {
            abort(404);
        }

        return $checkout;
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function addressLabel(array $address): string
    {
        $parts = array_filter([
            $address['first_name'] ?? null,
            $address['last_name'] ?? null,
            $address['address1'] ?? null,
            $address['city'] ?? null,
        ]);

        $label = implode(', ', $parts);

        return $label !== '' ? $label : 'Saved address';
    }
}
