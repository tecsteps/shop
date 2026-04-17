<?php

declare(strict_types=1);

use App\Enums\CheckoutStatus;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

/**
 * @return array{checkout: Checkout, inventory_item: InventoryItem}
 */
function createExpirableCheckoutFixture(bool $expired = true): array
{
    $store = Store::factory()->create([
        'default_currency' => 'EUR',
    ]);

    $product = Product::factory()->create([
        'store_id' => $store->id,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 2500,
        'currency' => 'EUR',
        'requires_shipping' => true,
    ]);

    $inventoryItem = InventoryItem::query()->create([
        'store_id' => $store->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 10,
        'quantity_reserved' => 1,
        'policy' => 'deny',
    ]);

    $cart = Cart::factory()->create([
        'store_id' => $store->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'status' => 'active',
    ]);

    CartLine::query()->create([
        'cart_id' => $cart->id,
        'variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'line_subtotal_amount' => 2500,
        'line_discount_amount' => 0,
        'line_total_amount' => 2500,
    ]);

    $checkout = Checkout::query()->create([
        'store_id' => $store->id,
        'cart_id' => $cart->id,
        'customer_id' => null,
        'status' => CheckoutStatus::PaymentSelected,
        'payment_method' => 'credit_card',
        'email' => 'buyer@example.test',
        'shipping_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'billing_address_json' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address1' => 'Main Street 1',
            'city' => 'Berlin',
            'country' => 'Germany',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
        'shipping_method_id' => null,
        'discount_code' => null,
        'tax_provider_snapshot_json' => null,
        'totals_json' => null,
        'expires_at' => $expired ? now()->subMinute() : now()->addHour(),
    ]);

    return [
        'checkout' => $checkout,
        'inventory_item' => $inventoryItem,
    ];
}

test('job expires overdue payment selected checkouts and releases reserved inventory', function (): void {
    $fixture = createExpirableCheckoutFixture();

    app(ExpireAbandonedCheckouts::class)->handle(app(CheckoutService::class));

    $fixture['checkout']->refresh();
    $fixture['inventory_item']->refresh();

    expect($fixture['checkout']->status)->toBe(CheckoutStatus::Expired)
        ->and((int) $fixture['inventory_item']->quantity_reserved)->toBe(0)
        ->and((int) $fixture['inventory_item']->quantity_on_hand)->toBe(10);
});

test('job does not touch active checkouts that are not expired', function (): void {
    $fixture = createExpirableCheckoutFixture(expired: false);

    app(ExpireAbandonedCheckouts::class)->handle(app(CheckoutService::class));

    $fixture['checkout']->refresh();
    $fixture['inventory_item']->refresh();

    expect($fixture['checkout']->status)->toBe(CheckoutStatus::PaymentSelected)
        ->and((int) $fixture['inventory_item']->quantity_reserved)->toBe(1)
        ->and((int) $fixture['inventory_item']->quantity_on_hand)->toBe(10);
});

test('scheduler registers the abandoned checkout expiration task', function (): void {
    Artisan::call('schedule:list');

    $output = Artisan::output();

    expect($output)->toContain('expire-abandoned-checkouts');
});
