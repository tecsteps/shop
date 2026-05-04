<?php

use App\Enums\CheckoutStatus;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
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
 * @return array{0: int, 1: ProductVariant, 2: string}
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

    $checkoutResponse = $api()
        ->postJson('/api/storefront/v1/checkouts', [
            'cart_id' => $cartId,
            'email' => 'buyer@example.test',
        ])
        ->assertCreated();
    $checkoutId = $checkoutResponse['data']['id'];
    $checkoutToken = $checkoutResponse['data']['access_token'];

    $addressResponse = $api()
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/address?token={$checkoutToken}", [
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
        ->putJson("/api/storefront/v1/checkouts/{$checkoutId}/shipping-method?token={$checkoutToken}", [
            'shipping_rate_id' => $addressResponse['data']['available_shipping_rates'][0]['id'],
        ])
        ->assertOk();

    return [$checkoutId, $variant, $checkoutToken];
}

test('storefront order api pays a checkout and exposes token-gated order lookup', function (): void {
    [$checkoutId, , $checkoutToken] = storefrontOrderApiCheckout($this);
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.41'])->withHeader('Host', 'shop.test');

    $payResponse = $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay?token={$checkoutToken}", [
            'payment_method' => 'credit_card',
            'card_number' => '4242 4242 4242 4242',
            'card_holder' => 'Test Buyer',
            'card_expiry' => '12/30',
            'card_cvc' => '123',
        ]);

    $payResponse
        ->assertOk()
        ->assertJsonPath('data.order_number', '#1016')
        ->assertJsonPath('data.financial_status', 'paid')
        ->assertJsonCount(1, 'data.lines');

    $token = $payResponse['data']['access_token'];
    $orderNumber = $payResponse['data']['order_number'];

    $api()
        ->getJson('/api/storefront/v1/orders/'.rawurlencode($orderNumber).'?token='.$token)
        ->assertOk()
        ->assertJsonPath('data.order_number', $orderNumber)
        ->assertJsonPath('data.total_amount', $payResponse['data']['total_amount']);

    $api()
        ->getJson('/api/storefront/v1/orders/'.rawurlencode($orderNumber).'?token=bad-token')
        ->assertNotFound();
});

test('storefront order api returns the same order when checkout payment is retried', function (): void {
    [$checkoutId, $variant, $checkoutToken] = storefrontOrderApiCheckout($this, '10.0.0.43');
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.43'])->withHeader('Host', 'shop.test');
    $payload = [
        'payment_method' => 'credit_card',
        'card_number' => '4242 4242 4242 4242',
        'card_holder' => 'Test Buyer',
        'card_expiry' => '12/30',
        'card_cvc' => '123',
    ];

    $firstResponse = $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay?token={$checkoutToken}", $payload)
        ->assertOk();
    $secondResponse = $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay?token={$checkoutToken}", $payload)
        ->assertOk();

    $orderId = $firstResponse->json('data.id');

    expect($secondResponse->json('data.id'))->toBe($orderId)
        ->and(Order::withoutGlobalScopes()->where('checkout_id', $checkoutId)->count())->toBe(1)
        ->and(Payment::query()->where('order_id', $orderId)->count())->toBe(1)
        ->and(InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->getKey())->firstOrFail()->quantity_reserved)->toBe(0);
});

test('storefront order api returns payment failures and releases reservations', function (): void {
    [$checkoutId, $variant, $checkoutToken] = storefrontOrderApiCheckout($this, '10.0.0.42');
    $api = fn () => $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.42'])->withHeader('Host', 'shop.test');

    $api()
        ->postJson("/api/storefront/v1/checkouts/{$checkoutId}/pay?token={$checkoutToken}", [
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
