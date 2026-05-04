<?php

use App\Models\DataExport;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\WebhookService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminOrderExportApiStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

function adminOrderExportApiUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

/**
 * @param  list<string>  $abilities
 * @return array{token: \App\Models\OauthToken, plain_text: string}
 */
function adminOrderExportApiToken(Store $store, array $abilities): array
{
    return app(WebhookService::class)->createApiToken($store, 'Order export integration', $abilities);
}

function adminOrderExportApiOrder(Store $store, array $attributes = []): Order
{
    return Order::factory()->paid()->create([
        'store_id' => $store->getKey(),
        'email' => 'export@example.test',
        'subtotal_amount' => 2500,
        'discount_amount' => 100,
        'shipping_amount' => 500,
        'tax_amount' => 0,
        'total_amount' => 2900,
        'currency' => $store->default_currency,
        ...$attributes,
    ]);
}

test('admin order export api creates completed csv exports', function (): void {
    Storage::fake('local');

    $store = adminOrderExportApiStore();
    $matching = adminOrderExportApiOrder($store, [
        'order_number' => '#EX1001',
        'created_at' => now()->subDays(2),
    ]);
    Fulfillment::factory()->shipped()->create([
        'order_id' => $matching->getKey(),
        'tracking_number' => 'TRACK-1001',
    ]);
    adminOrderExportApiOrder($store, [
        'order_number' => '#EX2001',
        'status' => 'pending',
        'financial_status' => 'pending',
        'created_at' => now()->subDays(2),
    ]);
    adminOrderExportApiOrder(Store::factory()->create(), [
        'order_number' => '#EX3001',
        'created_at' => now()->subDays(2),
    ]);

    $createResponse = $this->actingAs(adminOrderExportApiUser())
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/exports/orders", [
            'format' => 'csv',
            'filters' => [
                'status' => 'paid',
                'created_after' => now()->subDays(3)->toIso8601String(),
                'created_before' => now()->subDay()->toIso8601String(),
            ],
        ])
        ->assertAccepted()
        ->assertJsonPath('status', 'completed');

    $export = DataExport::query()->findOrFail($createResponse->json('export_id'));

    Storage::disk('local')->assertExists($export->storage_key);

    $showResponse = $this->actingAs(adminOrderExportApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/exports/{$export->getKey()}")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.format', 'csv')
        ->assertJsonPath('data.row_count', 1)
        ->assertJsonPath('data.download_expires_at', $export->download_expires_at?->toIso8601String());

    $csv = rawurldecode(Str::after((string) $showResponse->json('data.download_url'), ','));

    expect($csv)->toContain('order_number,created_at,status')
        ->and($csv)->toContain('#EX1001')
        ->and($csv)->toContain('TRACK-1001')
        ->and($csv)->not->toContain('#EX2001')
        ->and($csv)->not->toContain('#EX3001');
});

test('admin order export api enforces token abilities and store scope', function (): void {
    Storage::fake('local');

    $store = adminOrderExportApiStore();
    adminOrderExportApiOrder($store, ['order_number' => '#TOKEN-EXPORT']);
    $readToken = adminOrderExportApiToken($store, ['read-orders']);
    $wrongAbilityToken = adminOrderExportApiToken($store, ['read-products']);
    $otherStoreToken = adminOrderExportApiToken(Store::factory()->create(), ['read-orders']);

    $this->withToken($wrongAbilityToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/exports/orders", [
            'format' => 'csv',
        ])
        ->assertForbidden();

    $createResponse = $this->withToken($readToken['plain_text'])
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/exports/orders", [
            'format' => 'csv',
        ])
        ->assertAccepted();

    $export = DataExport::query()->findOrFail($createResponse->json('export_id'));

    $this->withToken($readToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/exports/{$export->getKey()}")
        ->assertOk();

    $this->withToken($otherStoreToken['plain_text'])
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/exports/{$export->getKey()}")
        ->assertForbidden();

    $otherStoreExport = DataExport::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
    ]);

    $this->actingAs(adminOrderExportApiUser())
        ->getJson("/api/admin/v1/stores/{$store->getKey()}/exports/{$otherStoreExport->getKey()}")
        ->assertNotFound();
});

test('admin order export api validates format and filters', function (): void {
    $store = adminOrderExportApiStore();

    $this->actingAs(adminOrderExportApiUser())
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/exports/orders", [
            'format' => 'xlsx',
            'filters' => [
                'status' => 'lost',
                'created_after' => now()->toIso8601String(),
                'created_before' => now()->subDay()->toIso8601String(),
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['format', 'filters.status', 'filters.created_before']);
});
