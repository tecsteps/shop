<?php

use App\Models\AnalyticsEvent;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

test('storefront analytics api accepts batches and deduplicates client events', function (): void {
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    $payload = [
        'events' => [
            [
                'type' => 'page_view',
                'session_id' => 'api-session-1',
                'client_event_id' => 'api-event-1',
                'properties' => ['url' => '/', 'channel' => 'storefront'],
                'occurred_at' => now()->toIso8601String(),
            ],
            [
                'type' => 'add_to_cart',
                'session_id' => 'api-session-1',
                'client_event_id' => 'api-event-2',
                'properties' => ['variant_id' => 1, 'quantity' => 2],
                'occurred_at' => now()->toIso8601String(),
            ],
        ],
    ];

    $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/analytics/events', $payload)
        ->assertAccepted()
        ->assertJsonPath('accepted', 2)
        ->assertJsonPath('rejected', 0);

    $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/analytics/events', [
            'events' => [$payload['events'][0]],
        ])
        ->assertAccepted()
        ->assertJsonPath('accepted', 0)
        ->assertJsonPath('rejected', 1);

    expect(AnalyticsEvent::withoutGlobalScopes()->where('store_id', $store->getKey())->whereIn('client_event_id', ['api-event-1', 'api-event-2'])->count())->toBe(2);
});

test('storefront analytics api validates event payload boundaries', function (): void {
    $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/analytics/events', [
            'events' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('events');

    $this->withHeader('Host', 'shop.test')
        ->postJson('/api/storefront/v1/analytics/events', [
            'events' => [[
                'type' => 'unknown',
                'session_id' => 'api-session-2',
                'client_event_id' => 'api-event-invalid',
                'properties' => ['a' => ['b' => ['c' => ['d' => true]]]],
                'occurred_at' => now()->subHours(2)->toIso8601String(),
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'events.0.type',
            'events.0.properties',
            'events.0.occurred_at',
        ]);
});
