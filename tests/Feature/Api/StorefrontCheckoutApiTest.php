<?php

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function storefrontApiCheckoutStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function storefrontApiCheckoutVariant(Store $store): ProductVariant
{
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'classic-cotton-t-shirt')
        ->firstOrFail();

    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->oldest('position')
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 20,
            'quantity_reserved' => 0,
        ]);

    return $variant->refresh();
}

/**
 * @return array<string, string>
 */
function storefrontApiCheckoutAddress(string $country = 'DE'): array
{
    return [
        'first_name' => 'Test',
        'last_name' => 'Buyer',
        'address1' => 'Main Street 1',
        'city' => 'Berlin',
        'province_code' => 'BE',
        'country' => $country,
        'country_code' => $country,
        'postal_code' => '10115',
    ];
}

test('storefront checkout api progresses through address shipping discount removal and payment selection', function (): void {
    $store = storefrontApiCheckoutStore();
    $variant = storefrontApiCheckoutVariant($store);
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.21'])->withHeader('Host', 'shop.test');

    $cartId = $api()
        ->postJson('/api/storefront/v1/carts')
        ->assertCreated()['data']['id'];

    $api()
        ->postJson("/api/storefront/v1/carts/{$cartId}/lines", [
            'variant_id' => $variant->getKey(),
            'quantity' => 2,
        ])
        ->assertCreated();

    $checkoutResponse = $api()
        ->postJson('/api/storefront/v1/checkouts', [
            'cart_id' => $cartId,
            'email' => 'buyer@example.test',
        ]);

    $checkoutResponse
        ->assertCreated()
        ->assertJsonPath('data.status', 'started')
        ->assertJsonPath('data.email', 'buyer@example.test')
        ->assertJsonPath('data.totals.subtotal', 4998)
        ->assertJsonPath('data.cart.line_count', 2);

    $checkoutId = $checkoutResponse['data']['id'];

    $addressResponse = $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address", [
            'shipping_address' => storefrontApiCheckoutAddress(),
        ]);

    $addressResponse
        ->assertOk()
        ->assertJsonPath('data.status', 'addressed')
        ->assertJsonPath('data.shipping_address.country', 'DE')
        ->assertJsonCount(3, 'data.available_shipping_rates');

    $shippingRateId = $addressResponse['data']['available_shipping_rates'][0]['id'];

    $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
            'shipping_rate_id' => $shippingRateId,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'shipping_selected')
        ->assertJsonPath('data.shipping_method_id', $shippingRateId)
        ->assertJsonPath('data.totals.shipping', 799);

    $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/apply-discount", ['code' => 'SAVE10'])
        ->assertOk()
        ->assertJsonPath('data.discount_code', 'SAVE10')
        ->assertJsonPath('data.totals.discount', 500);

    $api()
        ->deleteJson("/api/storefront/v1/checkouts/{$checkoutId}/discount")
        ->assertOk()
        ->assertJsonPath('data.discount_code', null)
        ->assertJsonPath('data.totals.discount', 0);

    $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/payment-method", [
            'payment_method' => 'credit_card',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'payment_selected')
        ->assertJsonPath('data.payment_method', 'credit_card');

    $checkout = Checkout::withoutGlobalScopes()->findOrFail($checkoutId);
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($checkout->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and($inventory->quantity_reserved)->toBe(2);
});

test('storefront checkout api rejects invalid addresses shipping methods and discounts', function (): void {
    $store = storefrontApiCheckoutStore();
    $variant = storefrontApiCheckoutVariant($store);
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.22'])->withHeader('Host', 'shop.test');

    $cartId = $api()
        ->postJson('/api/storefront/v1/carts')
        ->assertCreated()['data']['id'];

    $api()
        ->postJson("/api/storefront/v1/carts/{$cartId}/lines", [
            'variant_id' => $variant->getKey(),
            'quantity' => 1,
        ])
        ->assertCreated();

    $checkoutId = $api()
        ->postJson('/api/storefront/v1/checkouts', [
            'cart_id' => $cartId,
            'email' => 'buyer@example.test',
        ])
        ->assertCreated()['data']['id'];

    $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address", [
            'shipping_address' => array_diff_key(storefrontApiCheckoutAddress(), ['first_name' => true]),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('shipping_address.first_name');

    $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address", [
            'shipping_address' => storefrontApiCheckoutAddress(),
        ])
        ->assertOk();

    $otherStoreRate = ShippingRate::withoutGlobalScopes()
        ->whereHas('zone', fn ($query) => $query->withoutGlobalScopes()->where('store_id', Store::query()->whereKeyNot($store->getKey())->firstOrFail()->getKey()))
        ->firstOrFail();

    $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
            'shipping_rate_id' => $otherStoreRate->getKey(),
        ])
        ->assertUnprocessable();

    $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/apply-discount", ['code' => 'NOTREAL'])
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'discount_not_found');
});
