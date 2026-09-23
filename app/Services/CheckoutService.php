<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\TaxProviderUnavailableException;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\TaxSetting;
use App\Services\Tax\ManualTaxProvider;
use App\Services\Tax\StripeTaxProvider;
use App\ValueObjects\PricingResult;
use App\ValueObjects\TaxCalculationRequest;
use App\ValueObjects\TaxCalculationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly InventoryService $inventory,
        private readonly PaymentService $payments,
        private readonly ShippingCalculator $shippingCalculator,
    ) {}

    public function start(Cart $cart, ?Customer $customer = null): Checkout
    {
        $cart->loadMissing('lines.variant.product');

        if ($cart->status !== 'active' || $cart->lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Add an available product to your cart before checkout.']);
        }

        return Checkout::withoutGlobalScopes()->create([
            'store_id' => $cart->store_id,
            'cart_id' => $cart->id,
            'customer_id' => $customer?->id ?? $cart->customer_id,
            'status' => CheckoutStatus::Started->value,
            'discount_code' => $cart->discount_code,
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function setContact(Checkout $checkout, string $email): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Started->value) {
            throw new InvalidCheckoutTransitionException('Contact information cannot be changed at this checkout stage.');
        }

        Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->validate();

        $checkout->forceFill(['email' => $email])->save();

        return $this->recalculate($checkout->refresh());
    }

    /** @param array<string, mixed> $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     */
    public function setAddress(Checkout $checkout, string $email, array $shippingAddress, ?array $billingAddress = null): Checkout
    {
        if (! in_array($checkout->status, [CheckoutStatus::Started->value, CheckoutStatus::Addressed->value], true)) {
            throw new InvalidCheckoutTransitionException('Address details cannot be changed at this checkout stage.');
        }

        $shippingAddress = $this->normalizeAddress($shippingAddress);
        $billingAddress = $billingAddress === null ? null : $this->normalizeAddress($billingAddress);
        $requiresShipping = $this->requiresShipping($checkout);
        $requiredAddressField = $requiresShipping ? 'required' : 'nullable';
        $validCountryCode = static function (string $attribute, mixed $value, \Closure $fail): void {
            $countries = class_exists(\ResourceBundle::class)
                ? \ResourceBundle::create('en', 'ICUDATA-region')?->get('Countries')
                : null;

            $countryName = $countries instanceof \ResourceBundle
                ? $countries->get(strtoupper((string) $value))
                : null;

            if (! is_string($countryName) || in_array($countryName, ['Unknown Region', 'Pseudo-Accents', 'Pseudo-Bidi'], true)) {
                $fail('The country code must be a valid ISO 3166-1 alpha-2 code.');
            }
        };

        $validator = Validator::make([
            'email' => $email,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'shipping_address' => [$requiredAddressField, 'array'],
            'shipping_address.first_name' => [$requiredAddressField, 'string', 'max:255'],
            'shipping_address.last_name' => [$requiredAddressField, 'string', 'max:255'],
            'shipping_address.address1' => [$requiredAddressField, 'string', 'max:500'],
            'shipping_address.address2' => ['nullable', 'string', 'max:500'],
            'shipping_address.city' => [$requiredAddressField, 'string', 'max:255'],
            'shipping_address.province' => ['nullable', 'string', 'max:255'],
            'shipping_address.province_code' => ['nullable', 'string', 'max:10'],
            'shipping_address.country' => [$requiredAddressField, 'string', 'max:255'],
            'shipping_address.country_code' => [$requiredAddressField, 'string', 'size:2', $validCountryCode],
            'shipping_address.zip' => [$requiredAddressField, 'string', 'max:20'],
            'shipping_address.phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.first_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.last_name' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address1' => ['required_with:billing_address', 'string', 'max:500'],
            'billing_address.address2' => ['nullable', 'string', 'max:500'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.province' => ['nullable', 'string', 'max:255'],
            'billing_address.province_code' => ['nullable', 'string', 'max:10'],
            'billing_address.country' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.country_code' => ['required_with:billing_address', 'string', 'size:2', $validCountryCode],
            'billing_address.zip' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.phone' => ['nullable', 'string', 'max:50'],
        ]);

        $validator->validate();

        if ($requiresShipping) {
            $shippingAddress['country_code'] = strtoupper((string) $shippingAddress['country_code']);
            $shippingAddress['zip'] = (string) $shippingAddress['zip'];
        } else {
            $shippingAddress = [];
        }

        if ($billingAddress !== null) {
            $billingAddress['country_code'] = strtoupper((string) $billingAddress['country_code']);
            $billingAddress['zip'] = (string) $billingAddress['zip'];
        }

        $billingAddress ??= $shippingAddress;

        $checkout->forceFill([
            'email' => $email,
            'shipping_address_json' => $shippingAddress,
            'billing_address_json' => $billingAddress,
            'status' => CheckoutStatus::Addressed->value,
        ])->save();

        return $this->recalculate($checkout->refresh());
    }

    /** @return \Illuminate\Support\Collection<int, ShippingRate> */
    public function availableShippingRates(Checkout $checkout): \Illuminate\Support\Collection
    {
        return $this->shippingCalculator->ratesForCheckout($checkout);
    }

    public function selectShippingMethod(Checkout $checkout, ?int $shippingRateId = null): Checkout
    {
        if ($checkout->status !== CheckoutStatus::Addressed->value) {
            throw new InvalidCheckoutTransitionException('Provide a shipping address before selecting delivery.');
        }

        if (! $this->requiresShipping($checkout)) {
            $checkout->forceFill(['shipping_method_id' => null, 'shipping_amount' => 0, 'status' => CheckoutStatus::ShippingSelected->value])->save();

            return $this->recalculate($checkout->refresh());
        }

        $availableRates = $this->availableShippingRates($checkout);
        $rate = $availableRates->firstWhere('id', $shippingRateId);

        if (! $rate) {
            throw ValidationException::withMessages(['shipping_method_id' => 'Choose a valid shipping rate for your address.']);
        }

        $checkout->forceFill([
            'shipping_method_id' => $rate->id,
            'shipping_amount' => $rate->price_amount,
            'status' => CheckoutStatus::ShippingSelected->value,
        ])->save();

        return $this->recalculate($checkout->refresh());
    }

    public function applyDiscount(Checkout $checkout, string $code): Checkout
    {
        if (! in_array($checkout->status, [CheckoutStatus::Started->value, CheckoutStatus::Addressed->value, CheckoutStatus::ShippingSelected->value], true)) {
            throw new InvalidCheckoutTransitionException('Discount codes cannot be changed at this checkout stage.');
        }

        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $checkout->store_id)
            ->whereRaw('upper(code) = ?', [strtoupper(trim($code))])
            ->first();

        if (! $discount) {
            throw ValidationException::withMessages(['discount_code' => 'This discount code is not valid.']);
        }

        return DB::transaction(function () use ($checkout, $discount): Checkout {
            $checkout->forceFill(['discount_code' => $discount->code])->save();

            return $this->recalculate($checkout->refresh());
        });
    }

    public function removeDiscount(Checkout $checkout): Checkout
    {
        if (! in_array($checkout->status, [CheckoutStatus::Started->value, CheckoutStatus::Addressed->value, CheckoutStatus::ShippingSelected->value], true)) {
            throw new InvalidCheckoutTransitionException('Discount codes cannot be changed at this checkout stage.');
        }

        if (! $checkout->discount_code) {
            abort(404, 'No discount is applied to this checkout.');
        }

        $checkout->forceFill(['discount_code' => null])->save();

        return $this->recalculate($checkout->refresh());
    }

    public function selectPaymentMethod(Checkout $checkout, PaymentMethod $method): Checkout
    {
        if ($checkout->status !== CheckoutStatus::ShippingSelected->value) {
            throw new InvalidCheckoutTransitionException('Select your address and shipping method before payment.');
        }

        return DB::transaction(function () use ($checkout, $method): Checkout {
            $checkout = Checkout::withoutGlobalScopes()->with('cart.lines.variant.inventoryItem')->lockForUpdate()->findOrFail($checkout->id);

            foreach ($checkout->cart->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventory->reserve($line->variant->inventoryItem, $line->quantity);
                }
            }

            $checkout->forceFill([
                'payment_method' => $method->value,
                'status' => CheckoutStatus::PaymentSelected->value,
                'expires_at' => now()->addHours(24),
            ])->save();

            return $checkout->refresh();
        });
    }

    public function changePaymentMethod(Checkout $checkout, PaymentMethod $method): Checkout
    {
        if ($checkout->status !== CheckoutStatus::PaymentSelected->value) {
            throw new InvalidCheckoutTransitionException('Select your address and shipping method before changing payment.');
        }

        $checkout->forceFill(['payment_method' => $method->value])->save();

        return $checkout->refresh();
    }

    public function releaseReservations(Checkout $checkout): void
    {
        if ($checkout->status !== CheckoutStatus::PaymentSelected->value) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            $checkout->loadMissing('cart.lines.variant.inventoryItem');

            foreach ($checkout->cart->lines as $line) {
                if ($line->variant?->inventoryItem) {
                    $this->inventory->release($line->variant->inventoryItem, $line->quantity);
                }
            }
        });
    }

    public function expire(Checkout $checkout): void
    {
        if (in_array($checkout->status, [CheckoutStatus::Completed->value, CheckoutStatus::Expired->value], true)) {
            return;
        }

        DB::transaction(function () use ($checkout): void {
            $lockedCheckout = Checkout::withoutGlobalScopes()
                ->with('cart.lines.variant.inventoryItem')
                ->lockForUpdate()
                ->findOrFail($checkout->id);

            if ($lockedCheckout->status === CheckoutStatus::PaymentSelected->value) {
                foreach ($lockedCheckout->cart->lines as $line) {
                    if ($line->variant?->inventoryItem) {
                        $this->inventory->release($line->variant->inventoryItem, $line->quantity);
                    }
                }
            }

            $lockedCheckout->forceFill(['status' => CheckoutStatus::Expired->value])->save();
        });
    }

    /** @param array<string, mixed> $paymentDetails */
    public function pay(Checkout $checkout, array $paymentDetails = []): \App\Models\Order
    {
        if (Order::withoutGlobalScopes()->where('checkout_id', $checkout->id)->exists()) {
            return $this->payments->pay($checkout, $paymentDetails);
        }

        $checkout = $this->recalculate($checkout->refresh());

        return $this->payments->pay($checkout, $paymentDetails);
    }

    public function recalculate(Checkout $checkout): Checkout
    {
        $checkout->loadMissing('cart.lines.variant.product');
        $lines = $checkout->cart->lines->map(static fn ($line): array => [
            'id' => $line->id,
            'variant_id' => $line->variant_id,
            'product_id' => $line->variant?->product_id,
            'quantity' => $line->quantity,
            'unit_price_amount' => $line->unit_price_amount,
            'collection_ids' => $line->variant?->product?->collections?->pluck('id')->all() ?? [],
        ])->all();
        $subtotal = array_sum(array_map(static fn (array $line): int => $line['quantity'] * $line['unit_price_amount'], $lines));
        $codeDiscount = $checkout->discount_code
            ? Discount::withoutGlobalScopes()
                ->where('store_id', $checkout->store_id)
                ->whereRaw('upper(code) = ?', [strtoupper(trim($checkout->discount_code))])
                ->first()
            : null;
        $automaticDiscounts = $this->automaticDiscounts($checkout->store_id);
        $discounts = [...($codeDiscount ? [$codeDiscount] : []), ...$automaticDiscounts];
        $taxSettings = TaxSetting::query()->where('store_id', $checkout->store_id)->first();
        $address = $checkout->shipping_address_json ?? [];
        $selectedShippingAmount = (int) $checkout->shipping_amount;

        if ($checkout->shipping_method_id !== null) {
            $selectedRate = $this->availableShippingRates($checkout)->firstWhere('id', (int) $checkout->shipping_method_id);

            if ($selectedRate !== null) {
                $selectedShippingAmount = (int) $selectedRate->price_amount;
            }
        }

        $rates = $taxSettings?->taxRatesForCalculator() ?? [];
        $pricesIncludeTax = (bool) ($taxSettings?->prices_include_tax ?? false);
        $result = $this->pricing->calculate(
            $lines,
            $selectedShippingAmount,
            $discounts,
            0,
            $pricesIncludeTax,
            true,
            (string) ($checkout->cart->currency ?? 'USD'),
            $checkout->customer_id !== null,
            $checkout->customer_id,
            $checkout->email,
        );

        $discountedLineAmounts = array_map(function (array $line) use ($result): int {
            $variantId = (int) ($line['variant_id'] ?? $line['id'] ?? 0);
            $lineSubtotal = (int) $line['quantity'] * (int) $line['unit_price_amount'];

            return max(0, $lineSubtotal - (int) ($result->discountAllocations[$variantId] ?? 0));
        }, $lines);
        $taxRequest = new TaxCalculationRequest(
            (int) $checkout->store_id,
            $discountedLineAmounts,
            $result->shipping,
            $address,
            $rates,
            $taxSettings?->defaultTaxRateForCalculator() ?? 0,
            $pricesIncludeTax,
            true,
            (string) ($checkout->cart->currency ?? 'USD'),
            $taxSettings?->config_json ?? [],
        );
        $taxCalculation = $this->calculateTax($taxSettings, $taxRequest, $checkout);
        $result = new PricingResult(
            $result->subtotal,
            $result->discount,
            $result->shipping,
            $taxCalculation->lines,
            $taxCalculation->total,
            max(0, $result->subtotal - $result->discount + $result->shipping + ($pricesIncludeTax ? 0 : $taxCalculation->total)),
            $result->currency,
            $result->discountAllocations,
            $result->appliedDiscounts,
        );

        $checkout->forceFill([
            'subtotal_amount' => $result->subtotal,
            'discount_amount' => $result->discount,
            'shipping_amount' => $result->shipping,
            'tax_amount' => $result->taxTotal,
            'total_amount' => $result->total,
            'totals_json' => $result->toArray(),
            'tax_provider_snapshot_json' => $taxCalculation->snapshot,
        ])->save();

        return $checkout->refresh();
    }

    private function calculateTax(?TaxSetting $settings, TaxCalculationRequest $request, Checkout $checkout): TaxCalculationResult
    {
        if ($settings?->mode !== 'provider') {
            return app(ManualTaxProvider::class)->calculate($request);
        }

        if ($settings->provider === 'none') {
            return new TaxCalculationResult([], 0, [
                'provider' => 'none',
                'status' => 'skipped',
                'tax_lines' => [],
                'total_tax_amount' => 0,
            ]);
        }

        try {
            return app(StripeTaxProvider::class)->calculate($request);
        } catch (TaxProviderUnavailableException $exception) {
            $snapshot = [
                'provider' => 'stripe_tax',
                'status' => 'unavailable',
                'message' => $exception->getMessage(),
                'fallback' => $settings->config_json['fallback'] ?? 'allow',
            ];

            if (($settings->config_json['fallback'] ?? 'allow') === 'block') {
                $checkout->forceFill(['tax_provider_snapshot_json' => $snapshot])->save();

                throw ValidationException::withMessages(['tax' => 'Tax could not be calculated. Please try again later.']);
            }

            logger()->warning('Stripe Tax is unavailable; checkout continued without tax.', [
                'store_id' => $checkout->store_id,
                'checkout_id' => $checkout->id,
                'provider_error' => $exception->getMessage(),
            ]);

            return new TaxCalculationResult([], 0, [...$snapshot, 'status' => 'fallback']);
        }
    }

    /** @return list<Discount> */
    private function automaticDiscounts(int $storeId): array
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereNull('code')
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(static fn (Discount $discount): bool => ($discount->rules_json['activation_method'] ?? null) === 'automatic')
            ->values()
            ->all();
    }

    private function requiresShipping(Checkout $checkout): bool
    {
        $checkout->loadMissing('cart.lines.variant');

        return $checkout->cart->lines->contains(static fn ($line): bool => (bool) $line->variant?->requires_shipping);
    }

    /** @param array<string, mixed> $address
     * @return array<string, mixed>
     */
    private function normalizeAddress(array $address): array
    {
        $address['address1'] = $address['address1'] ?? $address['address_line_1'] ?? null;
        $address['address2'] = $address['address2'] ?? $address['address_line_2'] ?? null;
        $address['country_code'] = strtoupper((string) ($address['country_code'] ?? $address['country'] ?? ''));
        $address['country'] = $address['country'] ?? $address['country_code'];
        $address['zip'] = $address['zip'] ?? $address['postal_code'] ?? null;
        $address['postal_code'] = $address['postal_code'] ?? $address['zip'];
        $address['province'] = $address['province'] ?? $address['state'] ?? null;
        $address['province_code'] = $address['province_code'] ?? $address['state_code'] ?? null;

        return $address;
    }
}
