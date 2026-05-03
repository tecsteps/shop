<?php

use App\Enums\OrderStatus;
use App\Models\Export;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\ApiTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
    $this->seed();
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $this->otherStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
    $this->user = User::query()->where('email', 'admin@example.com')->firstOrFail();
});

function adminExportTokenFor($test, array $abilities): string
{
    return app(ApiTokenService::class)->create($test->store, $test->user, 'Export API test', $abilities)['plain_text_token'];
}

test('admin order export api queues and exposes a completed csv export', function (): void {
    Storage::fake('local');

    $paidOrder = Order::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('status', OrderStatus::Paid)
        ->firstOrFail();
    $storeUrl = route('api.admin.exports.orders.store', $this->store);

    $this->postJson($storeUrl)->assertUnauthorized();

    $this->withToken(adminExportTokenFor($this, ['write-orders']))
        ->postJson($storeUrl)
        ->assertForbidden();

    $response = $this->withToken(adminExportTokenFor($this, ['read-orders']))
        ->postJson($storeUrl, [
            'format' => 'csv',
            'filters' => [
                'status' => OrderStatus::Paid->value,
            ],
        ])
        ->assertAccepted()
        ->assertJsonPath('status', Export::StatusQueued);

    $export = Export::withoutGlobalScopes()
        ->whereKey($response->json('export_id'))
        ->firstOrFail();

    expect($export->status)->toBe(Export::StatusCompleted)
        ->and($export->row_count)->toBeGreaterThan(0)
        ->and($export->storage_key)->not->toBeNull();

    Storage::disk('local')->assertExists($export->storage_key);

    $csv = Storage::disk('local')->get($export->storage_key);

    expect($csv)->toContain('order_number,created_at,status,financial_status')
        ->and($csv)->toContain($paidOrder->order_number);

    $this->withToken(adminExportTokenFor($this, ['read-orders']))
        ->getJson(route('api.admin.exports.show', [$this->store, $export]))
        ->assertOk()
        ->assertJsonPath('data.id', $export->id)
        ->assertJsonPath('data.status', Export::StatusCompleted)
        ->assertJsonPath('data.format', Export::FormatCsv)
        ->assertJsonPath('data.row_count', $export->row_count)
        ->assertJsonPath('data.download_expires_at', $export->download_expires_at?->toISOString());
});

test('admin order export api validates export filters', function (): void {
    Storage::fake('local');

    $this->withToken(adminExportTokenFor($this, ['read-orders']))
        ->postJson(route('api.admin.exports.orders.store', $this->store), [
            'format' => 'xlsx',
            'filters' => [
                'status' => 'not-real',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['format', 'filters.status']);
});

test('admin order export api enforces store scoped token access', function (): void {
    $otherStoreExport = Export::factory()
        ->for($this->otherStore)
        ->completed()
        ->create();

    $this->withToken(adminExportTokenFor($this, ['read-orders']))
        ->getJson(route('api.admin.exports.show', [$this->otherStore, $otherStoreExport]))
        ->assertForbidden();

    $this->withToken(adminExportTokenFor($this, ['read-orders']))
        ->getJson(route('api.admin.exports.show', [$this->store, $otherStoreExport]))
        ->assertNotFound();
});
