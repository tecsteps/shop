<?php

use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\OrderExport;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    app()->instance('current_store', $this->store);
});

test('domain order events dispatch signed webhook deliveries', function (): void {
    Http::fake(['https://hooks.test/*' => Http::response([], 200)]);
    $subscription = WebhookSubscription::create(['event' => 'order.created', 'target_url' => 'https://hooks.test/orders', 'signing_secret_encrypted' => 'secret', 'status' => 'active']);
    $order = Order::query()->firstOrFail()->load('store');

    OrderCreated::dispatch($order);

    Http::assertSent(fn ($request): bool => $request->header('X-Platform-Event')[0] === 'order.created');
    expect(WebhookDelivery::query()->where('webhook_subscription_id', $subscription->getKey())->where('event', 'order.created')->exists())->toBeTrue();
});

test('admin order exports queue and expose generated csv status', function (): void {
    Storage::fake('public');
    $token = $this->admin->createToken('order-exporter', ['read-orders'])->plainTextToken;

    $response = $this->withToken($token)->postJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/exports/orders", ['format' => 'csv'])->assertStatus(202);
    $exportId = $response->json('export_id');
    $export = OrderExport::withoutGlobalScopes()->findOrFail($exportId);

    expect($export->status)->toBe('completed')->and($export->row_count)->toBeGreaterThan(0);
    Storage::disk('public')->assertExists($export->storage_key);
    $this->withToken($token)->getJson("http://shop.test/api/admin/v1/stores/{$this->store->getKey()}/exports/{$exportId}")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.row_count', $export->row_count);
});
