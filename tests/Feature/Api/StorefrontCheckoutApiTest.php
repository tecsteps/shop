<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\InventoryItem;
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

test('applies a valid discount code to checkout', function (): void {
    $hostname = 'checkout-discount-valid.test';
    $store = storefrontCheckoutApiCreateStore($hostname);
    $variant = storefrontCheckoutApiCreateVariant($store, 'discount-valid');
    $cart = storefrontCheckoutApiCreateCart($store, $variant);
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
        'shipping_method_id' => 1,
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
