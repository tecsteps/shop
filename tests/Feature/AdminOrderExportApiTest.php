<?php

use App\Jobs\GenerateOrderExport;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderExport;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function orderExportApiToken(User $user, Store $store, array $abilities = ['read-orders']): string
{
    $plainTextToken = 'shop_'.Str::random(64);
    $user->tokens()->create([
        'store_id' => $store->getKey(),
        'name' => 'order-export-test',
        'token' => hash('sha256', $plainTextToken),
        'abilities' => $abilities,
        'expires_at' => now()->addHour(),
    ]);

    return $plainTextToken;
}

function createOrderExportOrder(Store $store, string $number, string $email, string $status = 'paid'): Order
{
    $customer = Customer::factory()->create(['store_id' => $store->getKey(), 'email' => $email]);

    return Order::withoutGlobalScopes()->create([
        'store_id' => $store->getKey(),
        'customer_id' => $customer->getKey(),
        'order_number' => $number,
        'payment_method' => 'credit_card',
        'status' => $status,
        'financial_status' => $status === 'pending' ? 'pending' : 'paid',
        'fulfillment_status' => 'unfulfilled',
        'currency' => $store->default_currency,
        'subtotal_amount' => 5000,
        'total_amount' => 5000,
        'email' => $email,
        'placed_at' => now()->subDay(),
    ]);
}

it('queues store-scoped CSV exports and returns signed downloads when complete', function () {
    Queue::fake();
    Storage::fake('local');
    $store = shopStore();
    $otherStore = shopStore('export-other.test');
    createOrderExportOrder($store, '#EXP-PAID-01', 'paid@example.test');
    createOrderExportOrder($store, '#EXP-PENDING-01', 'pending@example.test', 'pending');
    createOrderExportOrder($otherStore, '#EXP-FOREIGN-01', 'foreign@example.test');
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $token = orderExportApiToken($user, $store);
    $basePath = "/api/admin/v1/stores/{$store->id}/exports";

    $queued = $this->withToken($token)->postJson("{$basePath}/orders", [
        'filters' => ['status' => 'paid', 'query' => 'paid@example.test'],
    ])->assertAccepted()
        ->assertJsonPath('status', 'queued')
        ->assertJsonPath('export_id', fn ($id) => is_int($id));

    $exportId = $queued->json('export_id');
    $export = OrderExport::query()->findOrFail($exportId);
    expect($export->filters_json)->toBe(['status' => 'paid', 'query' => 'paid@example.test']);
    Queue::assertPushed(GenerateOrderExport::class, fn (GenerateOrderExport $job): bool => $job->exportId === $exportId && $job->connection === 'database');

    (new GenerateOrderExport($exportId))->handle();
    $export->refresh();
    Storage::disk('local')->assertExists($export->storage_key);
    $csv = Storage::disk('local')->get($export->storage_key);
    expect($export->status)->toBe('completed')
        ->and($export->row_count)->toBe(1)
        ->and($csv)->toContain('#EXP-PAID-01')
        ->and($csv)->not->toContain('#EXP-PENDING-01')
        ->and($csv)->not->toContain('#EXP-FOREIGN-01');

    $csvLines = explode("\n", $csv);
    expect(str_getcsv($csvLines[0]))->toBe([
        'order_number', 'created_at', 'status', 'financial_status', 'fulfillment_status',
        'customer_email', 'customer_name', 'subtotal_amount', 'discount_amount', 'shipping_amount',
        'tax_amount', 'total_amount', 'currency', 'shipping_method', 'tracking_number',
    ]);

    $status = $this->withToken($token)->getJson("{$basePath}/{$exportId}")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.row_count', 1)
        ->assertJsonPath('data.format', 'csv');
    $downloadUrl = $status->json('data.download_url');
    expect($downloadUrl)->toBeString()->and($status->json('data.download_expires_at'))->not->toBeNull();

    $download = $this->get($downloadUrl)->assertOk();
    expect($download->streamedContent())->toContain('#EXP-PAID-01')
        ->and($download->headers->get('content-disposition'))->toContain("orders-export-{$exportId}.csv");
    $this->get($downloadUrl.'&tampered=1')->assertForbidden();

    $foreignExport = OrderExport::factory()->create(['store_id' => $otherStore->id]);
    $this->withToken($token)->getJson("{$basePath}/{$foreignExport->id}")->assertNotFound();
});

it('validates export format, object filters, dates, and store-owned customers', function () {
    Queue::fake();
    $store = shopStore();
    $otherStore = shopStore('export-validation-other.test');
    $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->id]);
    $user = User::factory()->create();
    $user->stores()->attach([$store->id => ['role' => 'owner'], $otherStore->id => ['role' => 'owner']]);
    $token = orderExportApiToken($user, $store);
    $url = "/api/admin/v1/stores/{$store->id}/exports/orders";

    $this->withToken($token)->postJson($url, ['format' => 'xlsx'])->assertUnprocessable()->assertInvalid(['format']);
    $this->withToken($token)->postJson($url, ['filters' => []])->assertUnprocessable()->assertInvalid(['filters']);
    $this->withToken($token)->postJson($url, ['filters' => ['customer_id' => $otherCustomer->id]])
        ->assertUnprocessable()
        ->assertInvalid(['filters.customer_id']);
    $this->withToken($token)->postJson($url, ['filters' => ['created_after' => '2026-02-02', 'created_before' => '2026-02-01']])
        ->assertUnprocessable()
        ->assertInvalid(['filters.created_before']);

    Queue::assertNothingPushed();
});

it('requires read-orders access and only provides download links for completed exports', function () {
    Queue::fake();
    $store = shopStore();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $readToken = orderExportApiToken($user, $store);
    $writeOnlyToken = orderExportApiToken($user, $store, ['write-orders']);
    $url = "/api/admin/v1/stores/{$store->id}/exports/orders";

    $this->withToken($writeOnlyToken)->postJson($url, [])->assertForbidden();
    $queued = $this->withToken($readToken)->postJson($url, [])->assertAccepted();
    $exportId = $queued->json('export_id');

    $this->withToken($readToken)->getJson("/api/admin/v1/stores/{$store->id}/exports/{$exportId}")
        ->assertOk()
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.download_url', null)
        ->assertJsonPath('data.download_expires_at', null);
});
