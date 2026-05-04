<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\StoreUserRole;
use App\Models\Fulfillment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminOrderApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminOrderApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminOrderApiUserWithRole(Store $store, StoreUserRole $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role->value,
        'created_at' => now(),
    ]);

    return $user;
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\PersonalAccessToken, plain_text: string}
 */
function adminOrderApiToken(Store $store, array $abilities): array
{
    return adminApiToken($store, $abilities);
}

/**
 * @return array{0: Order, 1: OrderLine, 2: ProductVariant}
 */
function adminOrderApiOrder(Store $store, array $orderAttributes = [], int $quantity = 2, int $unitPrice = 2500): array
{
    $product = Product::factory()
        ->withDefaultVariant($unitPrice)
        ->create(['store_id' => $store->getKey()]);
    $variant = ProductVariant::withoutGlobalScopes()
        ->where('product_id', $product->getKey())
        ->firstOrFail();

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->update([
            'quantity_on_hand' => 10,
            'quantity_reserved' => 0,
        ]);

    $total = $quantity * $unitPrice;
    $order = Order::factory()->paid()->create(array_merge([
        'store_id' => $store->getKey(),
        'subtotal_amount' => $total,
        'shipping_amount' => 0,
        'tax_amount' => 0,
        'total_amount' => $total,
        'email' => 'buyer@example.test',
    ], $orderAttributes));
    $line = OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product->getKey(),
        'variant_id' => $variant->getKey(),
        'title_snapshot' => 'Admin API Product',
        'sku_snapshot' => 'ADMIN-API-001',
        'quantity' => $quantity,
        'unit_price_amount' => $unitPrice,
        'total_amount' => $total,
    ]);

    Payment::factory()->create([
        'order_id' => $order->getKey(),
        'status' => $order->financial_status === FinancialStatus::Pending ? PaymentStatus::Pending : PaymentStatus::Captured,
        'amount' => $total,
        'currency' => $order->currency,
    ]);

    return [$order, $line, $variant];
}

test('admin order api lists and shows store scoped orders', function (): void {
    $store = adminOrderApiStore();
    [$order] = adminOrderApiOrder($store, [
        'order_number' => '#8001',
        'email' => 'alpha@example.test',
    ]);
    $otherStore = Store::factory()->create();
    adminOrderApiOrder($otherStore, [
        'order_number' => '#9001',
        'email' => 'other@example.test',
    ]);
    $readToken = adminApiBearerToken($store, ['read-orders'], adminOrderApiUser());

    $this->getJson("/api/admin/v1/stores/{$store->getKey()}/orders")
        ->assertUnauthorized();

    $this->withToken($readToken)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/orders?query=alpha")
        ->assertOk()
        ->assertJsonPath('data.0.order_number', '#8001')
        ->assertJsonMissing(['order_number' => '#9001']);

    $this->withToken($readToken)
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.order_number', '#8001')
        ->assertJsonPath('data.lines.0.title', 'Admin API Product');
});

test('admin order api creates refunds and fulfillments', function (): void {
    $store = adminOrderApiStore();
    [$order, $line] = adminOrderApiOrder($store, quantity: 2, unitPrice: 2500);
    $user = adminOrderApiUser();
    $writeToken = adminApiBearerToken($store, ['write-orders'], $user);

    $this->withToken($writeToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}/refunds", [
            'amount' => 1000,
            'reason' => 'Customer return',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 1000)
        ->assertJsonPath('data.status', 'processed');

    expect($order->refresh()->financial_status)->toBe(FinancialStatus::PartiallyRefunded);

    $this->withToken($writeToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}/fulfillments", [
            'line_items' => [
                ['order_line_id' => $line->getKey(), 'quantity' => 1],
            ],
            'tracking_company' => 'DHL',
            'tracking_number' => 'DHL123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.lines.0.quantity', 1);

    $fulfillment = Fulfillment::query()->where('order_id', $order->getKey())->firstOrFail();

    expect($order->refresh()->fulfillment_status)->toBe(FulfillmentStatus::Partial)
        ->and($fulfillment->tracking_number)->toBe('DHL123');
});

test('admin order api rejects orders outside the requested store', function (): void {
    $store = adminOrderApiStore();
    $otherStore = Store::factory()->create();
    [$otherOrder] = adminOrderApiOrder($otherStore, [
        'order_number' => '#9001',
    ]);

    $this->withToken(adminApiBearerToken($store, ['read-orders'], adminOrderApiUser()))
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$otherOrder->getKey()}")
        ->assertNotFound();
});

test('admin order api accepts scoped bearer tokens', function (): void {
    $store = adminOrderApiStore();
    adminOrderApiOrder($store, [
        'order_number' => '#8101',
        'email' => 'token@example.test',
    ]);
    $result = adminOrderApiToken($store, ['read-orders']);

    $this->withToken($result['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/orders?query=token")
        ->assertOk()
        ->assertJsonPath('data.0.order_number', '#8101');

    expect($result['token']->refresh()->last_used_at)->not->toBeNull();
});

test('admin order api enforces token store scope and abilities', function (): void {
    $store = adminOrderApiStore();
    [$order] = adminOrderApiOrder($store);
    $readOnly = adminOrderApiToken($store, ['read-orders']);
    $writeToken = adminOrderApiToken($store, ['write-orders']);
    $otherStore = Store::factory()->create();
    $otherStoreToken = adminOrderApiToken($otherStore, ['read-orders']);

    $this->withToken($readOnly['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}/refunds", [
            'amount' => 500,
            'reason' => 'Read-only token',
        ])
        ->assertForbidden();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/orders")
        ->assertForbidden();

    $this->withToken($writeToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}/refunds", [
            'amount' => 500,
            'reason' => 'Write token',
        ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 500);
});

test('admin order api applies role policies after token abilities pass', function (): void {
    $store = adminOrderApiStore();
    [$order, $line] = adminOrderApiOrder($store);
    $staff = adminOrderApiUserWithRole($store, StoreUserRole::Staff);
    $staffWriteToken = adminApiBearerToken($store, ['write-orders'], $staff);

    $this->withToken($staffWriteToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}/refunds", [
            'amount' => 500,
            'reason' => 'Staff token',
        ])
        ->assertForbidden();

    $this->withToken($staffWriteToken)
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/orders/{$order->getKey()}/fulfillments", [
            'line_items' => [
                ['order_line_id' => $line->getKey(), 'quantity' => 1],
            ],
        ])
        ->assertCreated();
});

test('admin order api rejects expired bearer tokens', function (): void {
    $store = adminOrderApiStore();
    adminOrderApiOrder($store);
    $result = adminOrderApiToken($store, ['read-orders']);
    $result['token']->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->withToken($result['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/orders")
        ->assertUnauthorized();
});
