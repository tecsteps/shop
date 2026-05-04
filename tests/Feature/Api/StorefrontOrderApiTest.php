<?php

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function storefrontOrderApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function storefrontOrderApiVariant(Store $store): ProductVariant
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
 * @return array{0: int, 1: ProductVariant}
 */
function storefrontOrderApiCheckout(object $testCase, string $remoteAddress = '10.0.0.41'): array
{
    $store = storefrontOrderApiStore();
    $variant = storefrontOrderApiVariant($store);
    $api = fn () => $testCase->withServerVariables(['REMOTE_ADDR' => $remoteAddress])->withHeader('Host', 'shop.test');

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

    $addressResponse = $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address", [
            'shipping_address' => [
                'first_name' => 'Test',
                'last_name' => 'Buyer',
                'address1' => 'Main Street 1',
                'city' => 'Berlin',
                'country' => 'DE',
                'postal_code' => '10115',
            ],
        ])
        ->assertOk();

    $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/shipping-method", [
            'shipping_rate_id' => $addressResponse['data']['available_shipping_rates'][0]['id'],
        ])
        ->assertOk();

    return [$checkoutId, $variant];
}

test('storefront order api pays a checkout and exposes token-gated order lookup', function (): void {
    [$checkoutId] = storefrontOrderApiCheckout($this);
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.41'])->withHeader('Host', 'shop.test');

    $payResponse = $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay", [
            'payment_method' => 'credit_card',
            'card_number' => '4242 4242 4242 4242',
            'card_holder' => 'Test Buyer',
            'card_expiry' => '12/30',
            'card_cvc' => '123',
        ]);

    $payResponse
        ->assertOk()
        ->assertJsonPath('data.order_number', '#1001')
        ->assertJsonPath('data.financial_status', 'paid')
        ->assertJsonCount(1, 'data.lines');

    $token = $payResponse['data']['access_token'];

    $api()
        ->getJson('/api/storefront/v1/orders/%231001?token='.$token)
        ->assertOk()
        ->assertJsonPath('data.order_number', '#1001')
        ->assertJsonPath('data.total_amount', $payResponse['data']['total_amount']);

    $api()
        ->getJson('/api/storefront/v1/orders/%231001?token=bad-token')
        ->assertNotFound();
});

test('storefront order api returns payment failures and releases reservations', function (): void {
    [$checkoutId, $variant] = storefrontOrderApiCheckout($this, '10.0.0.42');
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.42'])->withHeader('Host', 'shop.test');

    $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay", [
            'payment_method' => 'credit_card',
            'card_number' => '4000 0000 0000 0002',
            'card_holder' => 'Test Buyer',
            'card_expiry' => '12/30',
            'card_cvc' => '123',
        ])
        ->assertStatus(402);

    $checkout = Checkout::withoutGlobalScopes()->findOrFail($checkoutId);
    $inventory = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail();

    expect($checkout->status)->toBe(CheckoutStatus::ShippingSelected)
        ->and($inventory->quantity_reserved)->toBe(0);
});
