<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\StoreDomain;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-14 12:00:00'));
    Cache::flush();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

function storefrontCheckoutApiUrl(string $hostname, string $path): string
{
    return 'http://'.$hostname.$path;
}

function storefrontCheckoutApiCreateStore(string $hostname): Store
{
    $organization = Organization::query()->create([
        'name' => 'Checkout API Org',
        'billing_email' => 'billing+checkout-api@example.test',
    ]);

    $store = Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Checkout API Store',
        'handle' => 'checkout-api-store',
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);

    StoreDomain::query()->create([
        'store_id' => $store->id,
        'hostname' => $hostname,
        'type' => 'storefront',
        'is_primary' => true,
        'tls_mode' => 'managed',
        'created_at' => now(),
    ]);

    return $store;
}

function storefrontCheckoutApiCreateVariant(
    Store $store,
    string $suffix,
    int $price = 2500,
    bool $requiresShipping = true,
): ProductVariant {
    $product = Product::query()->create([
        'store_id' => $store->id,
        'title' => 'Checkout Product '.$suffix,
        'handle' => 'checkout-product-'.$suffix,
        'status' => 'active',
        'description_html' => null,
        'vendor' => null,
        'product_type' => null,
        'tags' => [],
        'published_at' => now(),
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'sku' => 'SKU-CHECKOUT-'.$suffix,
        'barcode' => null,
        'price_amount' => $price,
        'compare_at_amount' => null,
        'currency' => 'EUR',
        'weight_g' => 250,
        'requires_shipping' => $requiresShipping,
        'is_default' => true,
        'position' => 1,
        'status' => 'active',
    ]);

    InventoryItem::query()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
        'policy' => 'deny',
    ]);

    return $variant;
}

function storefrontCheckoutApiCreateCart(Store $store, ProductVariant $variant, int $quantity = 2): Cart
{
    $cart = Cart::query()->create([
        'store_id' => $store->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'cart_version' => 1,
        'status' => 'active',
    ]);

    $lineSubtotal = $variant->price_amount * $quantity;

    CartLine::query()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => $variant->price_amount,
        'line_subtotal_amount' => $lineSubtotal,
        'line_discount_amount' => 0,
        'line_total_amount' => $lineSubtotal,
    ]);

    return $cart;
}

function storefrontCheckoutApiCreateShippingRate(Store $store): ShippingRate
{
    $zone = ShippingZone::query()->create([
        'store_id' => $store->id,
        'name' => 'Germany',
        'countries_json' => ['DE'],
        'regions_json' => [],
    ]);

    return ShippingRate::query()->create([
        'zone_id' => $zone->id,
        'name' => 'Standard Shipping',
        'type' => 'flat',
        'config_json' => ['price_amount' => 500, 'currency' => 'EUR'],
        'is_active' => true,
    ]);
}

function storefrontCheckoutApiCreateDiscount(Store $store, string $code = 'WELCOME10'): Discount
{
    return Discount::query()->create([
        'store_id' => $store->id,
        'type' => DiscountType::Code,
        'code' => $code,
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => null,
        'usage_count' => 0,
        'rules_json' => [],
        'status' => DiscountStatus::Active,
    ]);
}

function storefrontCheckoutApiAddressPayload(): array
{
    return [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => '123 Main St',
            'address2' => 'Apt 4B',
            'city' => 'Berlin',
            'province' => 'Berlin',
            'province_code' => 'BE',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
            'phone' => '+49301234567',
        ],
        'billing_address' => null,
        'use_shipping_as_billing' => true,
    ];
}

function storefrontCheckoutApiPayPayload(): array
{
    return [
        'payment_method' => 'credit_card',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvc' => '123',
        'card_holder' => 'Jane Doe',
    ];
}

function storefrontCheckoutApiAssertOrderDiscountAllocations(Order $order, Discount $discount): void
{
    $order->loadMissing('lines');
    expect($order->lines->count())->toBeGreaterThan(0);

    $allocatedDiscountAmount = 0;
    $linesWithAllocations = 0;

    foreach ($order->lines as $line) {
        $allocations = $line->discount_allocations_json;

        expect($allocations)->toBeArray();

        if ($allocations === []) {
            continue;
        }

        $linesWithAllocations++;

        foreach ($allocations as $allocation) {
            expect(is_array($allocation))->toBeTrue();
            expect(array_key_exists('discount_id', $allocation))->toBeTrue();
            expect(array_key_exists('code', $allocation))->toBeTrue();
            expect(array_key_exists('amount', $allocation))->toBeTrue();
            expect($allocation['discount_id'])->toBe((int) $discount->id);
            expect($allocation['code'])->toBe((string) $discount->code);
            expect(is_int($allocation['amount']))->toBeTrue();
            expect($allocation['amount'])->toBeGreaterThan(0);

            $allocatedDiscountAmount += $allocation['amount'];
        }
    }

    expect($linesWithAllocations)->toBeGreaterThan(0);
    expect($allocatedDiscountAmount)->toBe((int) $order->discount_amount);
}

test('supports checkout transition flow from started to completed', function (): void {
    $hostname = 'checkout-flow.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'flow');
    $cart = storefrontCheckoutApiCreateCart($store, $variant);
    $shippingRate = storefrontCheckoutApiCreateShippingRate($store);

    $createCheckoutResponse = $this->postJson(
        storefrontCheckoutApiUrl($hostname, '/api/storefront/v1/checkouts'),
        [
            'cart_id' => $cart->id,
            'email' => 'buyer@example.test',
        ],
    );

    $createCheckoutResponse->assertCreated()
        ->assertJsonPath('status', 'started');

    $checkoutId = (int) $createCheckoutResponse->json('id');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/address"),
        storefrontCheckoutApiAddressPayload(),
    )->assertOk()
        ->assertJsonPath('status', 'addressed');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/shipping-method"),
        ['shipping_method_id' => $shippingRate->id],
    )->assertOk()
        ->assertJsonPath('status', 'shipping_selected');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/payment-method"),
        ['payment_method' => 'credit_card'],
    )->assertOk()
        ->assertJsonPath('status', 'payment_selected');

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/pay"),
        [
            'payment_method' => 'credit_card',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
            'card_holder' => 'Jane Doe',
        ],
    )->assertOk()
        ->assertJsonPath('status', 'completed');

    $this->assertDatabaseHas('orders', [
        'store_id' => $store->id,
        'checkout_id' => $checkoutId,
    ]);

    $inventoryItem = InventoryItem::query()
        ->where('variant_id', $variant->id)
        ->firstOrFail();

    expect((int) $inventoryItem->quantity_on_hand)->toBe(48);
    expect((int) $inventoryItem->quantity_reserved)->toBe(0);
});

test('completing checkout with a valid discount increments usage count and stores line allocations', function (): void {
    $hostname = 'checkout-discount-completion.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $primaryVariant = storefrontCheckoutApiCreateVariant($store, 'discount-completion-a', 2500);
    $secondaryVariant = storefrontCheckoutApiCreateVariant($store, 'discount-completion-b', 1500);
    $cart = storefrontCheckoutApiCreateCart($store, $primaryVariant, 1);
    $shippingRate = storefrontCheckoutApiCreateShippingRate($store);
    $discount = storefrontCheckoutApiCreateDiscount($store, 'WELCOME10');
    $discount->rules_json = [
        'applicable_product_ids' => [$primaryVariant->product_id],
    ];
    $discount->save();

    CartLine::query()->create([
        'cart_id' => $cart->id,
        'variant_id' => $secondaryVariant->id,
        'quantity' => 2,
        'unit_price_amount' => $secondaryVariant->price_amount,
        'line_subtotal_amount' => $secondaryVariant->price_amount * 2,
        'line_discount_amount' => 0,
        'line_total_amount' => $secondaryVariant->price_amount * 2,
    ]);

    $checkoutId = (int) $this->postJson(
        storefrontCheckoutApiUrl($hostname, '/api/storefront/v1/checkouts'),
        [
            'cart_id' => $cart->id,
            'email' => 'buyer@example.test',
        ],
    )->assertCreated()->json('id');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/address"),
        storefrontCheckoutApiAddressPayload(),
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/shipping-method"),
        ['shipping_method_id' => $shippingRate->id],
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/payment-method"),
        ['payment_method' => 'credit_card'],
    )->assertOk();

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/apply-discount"),
        ['code' => 'WELCOME10'],
    )->assertOk()
        ->assertJsonPath('discount_code', 'WELCOME10');

    $paymentResponse = $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/pay"),
        storefrontCheckoutApiPayPayload(),
    )->assertOk()
        ->assertJsonPath('status', 'completed');

    $orderId = (int) $paymentResponse->json('order.id');

    $discount->refresh();
    expect((int) $discount->usage_count)->toBe(1);

    $order = Order::query()
        ->where('id', $orderId)
        ->where('checkout_id', $checkoutId)
        ->firstOrFail();

    expect((int) $order->discount_amount)->toBeGreaterThan(0);

    storefrontCheckoutApiAssertOrderDiscountAllocations($order, $discount);

    $order->loadMissing('lines');
    $primaryLine = $order->lines->firstWhere('variant_id', $primaryVariant->id);
    $secondaryLine = $order->lines->firstWhere('variant_id', $secondaryVariant->id);

    expect($primaryLine)->not->toBeNull();
    expect($secondaryLine)->not->toBeNull();
    expect(is_array($primaryLine?->discount_allocations_json))->toBeTrue();
    expect($primaryLine?->discount_allocations_json)->not->toBeEmpty();
    expect($secondaryLine?->discount_allocations_json)->toBe([]);
});

test('pay succeeds when an applied discount becomes invalid before payment', function (): void {
    $hostname = 'checkout-discount-invalidated-on-pay.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'discount-invalidated', 2500);
    $cart = storefrontCheckoutApiCreateCart($store, $variant, 1);
    $shippingRate = storefrontCheckoutApiCreateShippingRate($store);
    $discount = storefrontCheckoutApiCreateDiscount($store, 'WELCOME10');

    $checkoutId = (int) $this->postJson(
        storefrontCheckoutApiUrl($hostname, '/api/storefront/v1/checkouts'),
        [
            'cart_id' => $cart->id,
            'email' => 'buyer@example.test',
        ],
    )->assertCreated()->json('id');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/address"),
        storefrontCheckoutApiAddressPayload(),
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/shipping-method"),
        ['shipping_method_id' => $shippingRate->id],
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/payment-method"),
        ['payment_method' => 'credit_card'],
    )->assertOk();

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/apply-discount"),
        ['code' => 'WELCOME10'],
    )->assertOk()
        ->assertJsonPath('discount_code', 'WELCOME10');

    $discount->rules_json = [
        'applicable_product_ids' => [999999],
    ];
    $discount->save();

    $paymentResponse = $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/pay"),
        storefrontCheckoutApiPayPayload(),
    )->assertOk()
        ->assertJsonPath('status', 'completed');

    $orderId = (int) $paymentResponse->json('order.id');

    $discount->refresh();
    expect((int) $discount->usage_count)->toBe(0);

    $checkout = Checkout::query()->findOrFail($checkoutId);
    expect($checkout->discount_code)->toBeNull();

    $order = Order::query()
        ->whereKey($orderId)
        ->where('checkout_id', $checkoutId)
        ->firstOrFail();

    expect((int) $order->discount_amount)->toBe(0);

    $order->loadMissing('lines');

    foreach ($order->lines as $line) {
        expect($line->discount_allocations_json)->toBe([]);
    }
});

test('pay endpoint is idempotent for repeated requests on the same checkout', function (): void {
    $hostname = 'checkout-idempotent-pay.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'idempotent');
    $cart = storefrontCheckoutApiCreateCart($store, $variant);
    $shippingRate = storefrontCheckoutApiCreateShippingRate($store);
    $discount = storefrontCheckoutApiCreateDiscount($store, 'WELCOME10');

    $checkoutId = (int) $this->postJson(
        storefrontCheckoutApiUrl($hostname, '/api/storefront/v1/checkouts'),
        [
            'cart_id' => $cart->id,
            'email' => 'buyer@example.test',
        ],
    )->assertCreated()->json('id');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/address"),
        storefrontCheckoutApiAddressPayload(),
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/shipping-method"),
        ['shipping_method_id' => $shippingRate->id],
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/payment-method"),
        ['payment_method' => 'credit_card'],
    )->assertOk();

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/apply-discount"),
        ['code' => 'WELCOME10'],
    )->assertOk()
        ->assertJsonPath('discount_code', 'WELCOME10');

    $firstPayment = $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/pay"),
        storefrontCheckoutApiPayPayload(),
    )->assertOk();

    $firstOrderId = (int) $firstPayment->json('order.id');

    $discount->refresh();
    expect((int) $discount->usage_count)->toBe(1);

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/pay"),
        storefrontCheckoutApiPayPayload(),
    )->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('order.id', $firstOrderId);

    $discount->refresh();
    expect((int) $discount->usage_count)->toBe(1);

    $order = Order::query()
        ->where('id', $firstOrderId)
        ->where('checkout_id', $checkoutId)
        ->firstOrFail();

    expect((int) $order->discount_amount)->toBeGreaterThan(0);

    storefrontCheckoutApiAssertOrderDiscountAllocations($order, $discount);

    expect(Order::query()
        ->where('store_id', $store->id)
        ->where('checkout_id', $checkoutId)
        ->count())->toBe(1);
});

test('returns 409 for invalid checkout state transition', function (): void {
    $hostname = 'checkout-invalid-transition.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'invalid-transition');
    $cart = storefrontCheckoutApiCreateCart($store, $variant);

    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => null,
        'status' => 'started',
        'payment_method' => null,
        'email' => 'buyer@example.test',
        'shipping_address_json' => null,
        'billing_address_json' => null,
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => null,
    ]);

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkout->id}/pay"),
        ['payment_method' => 'credit_card'],
    )->assertStatus(409);
});

test('expiring a payment selected checkout releases reserved inventory', function (): void {
    $hostname = 'checkout-expiry-releases-inventory.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'expiry-release', 2500);
    $cart = storefrontCheckoutApiCreateCart($store, $variant, 1);
    $shippingRate = storefrontCheckoutApiCreateShippingRate($store);

    $checkoutId = (int) $this->postJson(
        storefrontCheckoutApiUrl($hostname, '/api/storefront/v1/checkouts'),
        [
            'cart_id' => $cart->id,
            'email' => 'buyer@example.test',
        ],
    )->assertCreated()->json('id');

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/address"),
        storefrontCheckoutApiAddressPayload(),
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/shipping-method"),
        ['shipping_method_id' => $shippingRate->id],
    )->assertOk();

    $this->putJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}/payment-method"),
        ['payment_method' => 'credit_card'],
    )->assertOk()
        ->assertJsonPath('status', 'payment_selected');

    $inventoryItem = InventoryItem::query()
        ->where('variant_id', $variant->id)
        ->firstOrFail();

    expect((int) $inventoryItem->quantity_reserved)->toBe(1);

    Checkout::query()->whereKey($checkoutId)->update([
        'expires_at' => now()->subMinute(),
    ]);

    $this->getJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkoutId}"),
    )->assertStatus(422)
        ->assertJsonPath('error_code', 'checkout_expired');

    $inventoryItem->refresh();

    expect((int) $inventoryItem->quantity_reserved)->toBe(0);

    $checkout = Checkout::query()->findOrFail($checkoutId);
    expect((string) $checkout->status->value)->toBe('expired');
});

test('applies a valid discount code to checkout', function (): void {
    $hostname = 'checkout-discount-valid.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'discount-valid');
    $cart = storefrontCheckoutApiCreateCart($store, $variant);
    $shippingRate = storefrontCheckoutApiCreateShippingRate($store);
    storefrontCheckoutApiCreateDiscount($store, 'WELCOME10');

    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => null,
        'status' => 'shipping_selected',
        'payment_method' => null,
        'email' => 'buyer@example.test',
        'shipping_address_json' => storefrontCheckoutApiAddressPayload()['shipping_address'],
        'billing_address_json' => storefrontCheckoutApiAddressPayload()['shipping_address'],
        'shipping_method_id' => $shippingRate->id,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->addDay(),
    ]);

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkout->id}/apply-discount"),
        ['code' => 'WELCOME10'],
    )->assertOk()
        ->assertJsonPath('discount_code', 'WELCOME10');
});

test('returns 422 for invalid discount code', function (): void {
    $hostname = 'checkout-discount-invalid.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'discount-invalid');
    $cart = storefrontCheckoutApiCreateCart($store, $variant);

    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => null,
        'status' => 'shipping_selected',
        'payment_method' => null,
        'email' => 'buyer@example.test',
        'shipping_address_json' => storefrontCheckoutApiAddressPayload()['shipping_address'],
        'billing_address_json' => storefrontCheckoutApiAddressPayload()['shipping_address'],
        'shipping_method_id' => 1,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => now()->addDay(),
    ]);

    $this->postJson(
        storefrontCheckoutApiUrl($hostname, "/api/storefront/v1/checkouts/{$checkout->id}/apply-discount"),
        ['code' => 'INVALID'],
    )->assertUnprocessable();
});

test('returns 404 for unknown checkout ids', function (): void {
    $hostname = 'checkout-unknown.test';
    storefrontCheckoutApiCreateStore($hostname);

    $this->getJson(
        storefrontCheckoutApiUrl($hostname, '/api/storefront/v1/checkouts/999999'),
    )->assertNotFound();
});
