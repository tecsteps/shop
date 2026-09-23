<?php

namespace App\Services;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidCheckoutTransitionException;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\TaxSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PricingEngine $pricing,
        private readonly InventoryService $inventory,
        private readonly PaymentService $payments,
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

        return $checkout->refresh();
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

        Validator::make([
            'email' => $email,
            ...$shippingAddress,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'address1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'country' => ['nullable', 'string', 'size:2'],
            'zip' => ['nullable', 'string', 'max:24'],
            'postal_code' => ['nullable', 'string', 'max:24'],
        ])->after(function ($validator) use ($shippingAddress): void {
            if (blank($shippingAddress['country_code'] ?? $shippingAddress['country'] ?? null)) {
                $validator->errors()->add('country_code', 'The country code field is required.');
            }

            if (blank($shippingAddress['zip'] ?? $shippingAddress['postal_code'] ?? null)) {
                $validator->errors()->add('postal_code', 'The postal code field is required.');
            }
        })->validate();

        $shippingAddress['country_code'] = strtoupper((string) ($shippingAddress['country_code'] ?? $shippingAddress['country']));
        $shippingAddress['zip'] = $shippingAddress['zip'] ?? $shippingAddress['postal_code'];
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
        if (! $this->requiresShipping($checkout)) {
            return collect();
        }

        $countryCode = strtoupper((string) ($checkout->shipping_address_json['country_code'] ?? $checkout->shipping_address_json['country'] ?? ''));
        $subtotal = (int) $checkout->cart()->withSum('lines', 'line_subtotal_amount')->value('lines_sum_line_subtotal_amount');
        $zones = ShippingZone::withoutGlobalScopes()->where('store_id', $checkout->store_id)->where('is_active', true)->with('rates')->get();

        return $zones
            ->filter(static fn (ShippingZone $zone): bool => in_array($countryCode, array_map('strtoupper', $zone->countries ?? []), true))
            ->flatMap(static fn (ShippingZone $zone) => $zone->rates->filter(static function (ShippingRate $rate) use ($subtotal): bool {
                if (! $rate->is_active) {
                    return false;
                }

                return ($rate->min_order_amount === null || $subtotal >= $rate->min_order_amount)
                    && ($rate->max_order_amount === null || $subtotal <= $rate->max_order_amount);
            }))
            ->values();
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
            ->firstOrFail();

        $checkout->forceFill(['discount_code' => $discount->code])->save();

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

    /** @param array<string, mixed> $paymentDetails */
    public function pay(Checkout $checkout, array $paymentDetails = []): \App\Models\Order
    {
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
        $discount = $checkout->discount_code
            ? Discount::withoutGlobalScopes()->where('store_id', $checkout->store_id)->where('code', $checkout->discount_code)->first()
            : null;
        $taxSettings = TaxSetting::query()->where('store_id', $checkout->store_id)->first();
        $address = $checkout->shipping_address_json ?? [];
        $rates = $taxSettings?->rates_json ?? [];
        $country = (string) ($address['country_code'] ?? $address['country'] ?? '');
        $province = $address['province_code'] ?? null;
        $taxRate = app(TaxCalculator::class)->rateForAddress($rates, $country, $province, (int) ($taxSettings?->default_rate ?? 0));
        $result = $this->pricing->calculate(
            $lines,
            (int) $checkout->shipping_amount,
            $discount,
            $taxRate,
            (bool) ($taxSettings?->prices_include_tax ?? false),
            true,
            (string) ($checkout->cart->currency ?? 'USD'),
            $checkout->customer_id !== null,
        );

        $checkout->forceFill([
            'subtotal_amount' => $result->subtotal,
            'discount_amount' => $result->discount,
            'shipping_amount' => $result->shipping,
            'tax_amount' => $result->taxTotal,
            'total_amount' => $result->total,
            'totals_json' => $result->toArray(),
        ])->save();

        return $checkout->refresh();
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
