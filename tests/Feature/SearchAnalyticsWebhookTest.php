<?php

use App\Jobs\AggregateAnalytics;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\AnalyticsService;
use App\Services\SearchService;
use App\Services\WebhookService;
use Carbon\CarbonImmutable;
use Database\Seeders\ShopSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    $this->seed(ShopSeeder::class);
    $this->store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $this->store);
});

test('search is tenant scoped, paginated, and logs the query', function (): void {
    $search = new SearchService;
    $results = $search->search($this->store, 'classic', [], 12);

    expect($results->total())->toBe(1)
        ->and($results->first()->title)->toBe('Classic Cotton T-Shirt')
        ->and(SearchQuery::query()->where('query', 'classic')->count())->toBe(1);
});

test('product changes are synchronized to the FTS index and autocomplete', function (): void {
    $product = Product::query()->where('handle', 'classic-cotton-t-shirt')->firstOrFail();
    $product->update(['title' => 'Classic Cotton Tee']);

    expect(DB::table('products_fts')->where('product_id', $product->getKey())->value('title'))->toBe('Classic Cotton Tee')
        ->and((new SearchService)->autocomplete($this->store, 'Classic')->first()->title)->toBe('Classic Cotton Tee');
});

test('analytics events aggregate idempotently into daily metrics', function (): void {
    $date = CarbonImmutable::yesterday();
    $analytics = new AnalyticsService;

    foreach (['page_view', 'page_view', 'add_to_cart', 'checkout_started'] as $type) {
        $event = $analytics->track($this->store, $type, ['source' => 'test'], 'session-1');
        $event->forceFill(['created_at' => $date])->save();
    }

    AggregateAnalytics::dispatchSync($this->store, $date->toDateString());
    AggregateAnalytics::dispatchSync($this->store, $date->toDateString());

    $daily = AnalyticsDaily::query()->whereDate('date', $date)->firstOrFail();

    expect($daily->visits_count)->toBe(2)
        ->and($daily->add_to_cart_count)->toBe(1)
        ->and($daily->checkout_started_count)->toBe(1)
        ->and(AnalyticsEvent::query()->where('store_id', $this->store->getKey())->count())->toBe(4);
});

test('webhooks are signed and delivered with platform headers', function (): void {
    Http::fake(['https://hooks.test/*' => Http::response(['ok' => true], 200)]);
    $subscription = WebhookSubscription::create(['event' => 'order.created', 'target_url' => 'https://hooks.test/orders', 'signing_secret_encrypted' => 'test-secret', 'status' => 'active']);

    (new WebhookService)->dispatch($this->store, 'order.created', ['order_id' => 1001]);

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('X-Platform-Signature')
            && $request->header('X-Platform-Event')[0] === 'order.created'
            && $request->header('X-Platform-Delivery-Id') !== null;
    });

    expect(WebhookDelivery::query()->where('webhook_subscription_id', $subscription->getKey())->firstOrFail()->status)->toBe('delivered')
        ->and((new WebhookService)->verify('{"order_id":1001}', (new WebhookService)->sign('{"order_id":1001}', 'test-secret'), 'test-secret'))->toBeTrue();
});
