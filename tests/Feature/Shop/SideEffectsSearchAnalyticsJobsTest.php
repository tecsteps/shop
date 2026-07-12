<?php

use App\Contracts\DnsResolver;
use App\Events\OrderCreated;
use App\Jobs\AggregateAnalytics;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\DeliverWebhook;
use App\Jobs\ProcessMediaUpload;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Notifications\OrderLifecycleNotification;
use App\Services\AnalyticsService;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\SearchService;
use App\Services\WebhookService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

beforeEach(function (): void {
    app()->instance(DnsResolver::class, new class implements DnsResolver
    {
        public function resolve(string $hostname): array
        {
            return ['8.8.8.8'];
        }
    });
});

describe('search indexing and discovery', function () {
    it('finds active published products by prefix and isolates tenants', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        Product::factory()->for($storeA)->create([
            'title' => 'Blue Cotton T-Shirt',
            'handle' => 'blue-cotton-shirt',
            'description_html' => '<p>Organic everyday essential</p>',
            'vendor' => 'Acme Basics',
        ]);
        Product::factory()->for($storeA)->create(['title' => 'Red Wool Sweater', 'handle' => 'red-wool-sweater']);
        Product::factory()->for($storeA)->draft()->create(['title' => 'Cotton Draft', 'handle' => 'cotton-draft']);
        Product::factory()->for($storeA)->archived()->create(['title' => 'Cotton Archive', 'handle' => 'cotton-archive']);
        Product::factory()->for($storeB)->create(['title' => 'Cotton From Other Store', 'handle' => 'other-cotton']);
        bindStore($storeA);

        $results = app(SearchService::class)->search($storeA, 'cott');

        expect($results->total())->toBe(1)
            ->and($results->items()[0]->title)->toBe('Blue Cotton T-Shirt')
            ->and(SearchQuery::query()->latest('id')->first()->query)->toBe('cott')
            ->and(SearchQuery::query()->latest('id')->first()->results_count)->toBe(1);
    });

    it('updates and removes FTS records through product observation', function () {
        $store = createStoreContext()['store'];
        $product = Product::factory()->for($store)->create(['title' => 'Original Search Phrase', 'handle' => 'search-observer']);
        $search = app(SearchService::class);

        expect($search->search($store, 'Original')->total())->toBe(1);
        $product->update(['title' => 'Replacement Discovery Phrase']);
        expect($search->search($store, 'Original')->total())->toBe(0)
            ->and($search->search($store, 'Replace')->total())->toBe(1);
        $product->delete();
        expect($search->search($store, 'Replace')->total())->toBe(0);
    });

    it('paginates results and limits autocomplete suggestions', function () {
        $store = createStoreContext()['store'];
        foreach (range(1, 25) as $number) {
            Product::factory()->for($store)->create([
                'title' => "Summer Item {$number}",
                'handle' => "summer-item-{$number}",
            ]);
        }
        $search = app(SearchService::class);
        LengthAwarePaginator::currentPageResolver(fn (): int => 2);
        $page = $search->search($store, 'Summer', perPage: 12);
        LengthAwarePaginator::currentPageResolver(fn (): int => 1);
        $suggestions = $search->autocomplete($store, 'Sum', 5);

        expect($page->total())->toBe(25)
            ->and($page->currentPage())->toBe(2)
            ->and($page->items())->toHaveCount(12)
            ->and($suggestions)->toHaveCount(5);
    });

    it('sanitizes FTS operators instead of exposing query syntax errors', function () {
        $store = createStoreContext()['store'];
        Product::factory()->for($store)->create(['title' => 'Safe Cotton Product', 'handle' => 'safe-cotton']);

        $results = app(SearchService::class)->search($store, 'cotton" OR *');

        expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
    });
});

describe('analytics ingestion and aggregation', function () {
    it('deduplicates client events inside a store but not across stores', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $service = app(AnalyticsService::class);
        $first = $service->track($storeA, 'page_view', ['path' => '/'], 'session-a', clientEventId: 'client-1');
        $duplicate = $service->track($storeA, 'page_view', ['path' => '/again'], 'session-b', clientEventId: 'client-1');
        $other = $service->track($storeB, 'page_view', ['path' => '/'], 'session-c', clientEventId: 'client-1');

        expect($duplicate->id)->toBe($first->id)
            ->and($other->id)->not->toBe($first->id)
            ->and(AnalyticsEvent::withoutGlobalScopes()->where('client_event_id', 'client-1')->count())->toBe(2);
    });

    it('rejects unsupported event types', function () {
        $store = createStoreContext()['store'];

        app(AnalyticsService::class)->track($store, 'execute_script');
    })->throws(InvalidArgumentException::class);

    it('aggregates visits conversions revenue and AOV idempotently', function () {
        $store = createStoreContext()['store'];
        $date = CarbonImmutable::parse('2026-07-10 12:00:00', 'UTC');
        $analytics = app(AnalyticsService::class);
        foreach (['visitor-a', 'visitor-a', 'visitor-b'] as $index => $session) {
            $analytics->track($store, 'page_view', [], $session, clientEventId: "page-{$index}", occurredAt: $date);
        }
        foreach (range(1, 3) as $index) {
            $analytics->track($store, 'add_to_cart', [], 'visitor-a', clientEventId: "cart-{$index}", occurredAt: $date);
        }
        foreach ([1000, 2000, 3000] as $index => $total) {
            $analytics->track($store, 'checkout_completed', ['total_amount' => $total], 'buyer', clientEventId: "order-{$index}", occurredAt: $date);
        }
        $analytics->track($store, 'checkout_started', [], 'buyer', clientEventId: 'checkout-start', occurredAt: $date);

        $first = $analytics->aggregate($store, '2026-07-10');
        $second = $analytics->aggregate($store, '2026-07-10');

        expect($first->orders_count)->toBe(3)
            ->and($first->revenue_amount)->toBe(6000)
            ->and($first->aov_amount)->toBe(2000)
            ->and($first->visits_count)->toBe(2)
            ->and($first->add_to_cart_count)->toBe(3)
            ->and($first->checkout_started_count)->toBe(1)
            ->and($second->getAttributes())->toMatchArray($first->getAttributes())
            ->and(AnalyticsDaily::withoutGlobalScopes()->where('store_id', $store->id)->count())->toBe(1);
    });

    it('aggregates every active store through the scheduled job', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $service = app(AnalyticsService::class);
        $date = now()->subDay()->startOfDay()->addHour();
        $service->track($storeA, 'checkout_completed', ['total_amount' => 1000], 'a', clientEventId: 'a-order', occurredAt: $date);
        $service->track($storeB, 'checkout_completed', ['total_amount' => 2500], 'b', clientEventId: 'b-order', occurredAt: $date);

        (new AggregateAnalytics($date->toDateString()))->handle($service);

        expect(AnalyticsDaily::withoutGlobalScopes()->where('date', $date->toDateString())->count())->toBe(2)
            ->and(AnalyticsDaily::withoutGlobalScopes()->where('store_id', $storeA->id)->value('revenue_amount'))->toBe(1000)
            ->and(AnalyticsDaily::withoutGlobalScopes()->where('store_id', $storeB->id)->value('revenue_amount'))->toBe(2500);
    });
});

describe('webhook dispatch delivery and circuit breaking', function () {
    it('queues one delivery for each matching active subscription', function () {
        Queue::fake();
        $store = createStoreContext()['store'];
        $matching = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/orders',
            'signing_secret_encrypted' => 'top-secret',
            'status' => 'active',
        ]);
        WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'product.created',
            'target_url' => 'https://hooks.example/products',
            'signing_secret_encrypted' => 'top-secret',
            'status' => 'active',
        ]);
        WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/paused',
            'signing_secret_encrypted' => 'top-secret',
            'status' => 'paused',
        ]);

        app(WebhookService::class)->dispatch($store, 'order.created', ['order_id' => 42]);

        expect($matching->deliveries)->toHaveCount(1)
            ->and($matching->deliveries->first()->status->value)->toBe('pending')
            ->and($matching->deliveries->first()->attempt_count)->toBe(0);
        Queue::assertPushed(DeliverWebhook::class, 1);
        Queue::assertPushed(DeliverWebhook::class, fn (DeliverWebhook $job): bool => $job->eventType === 'order.created' && $job->payload === ['order_id' => 42]);
    });

    it('posts signed JSON and records a successful response', function () {
        Http::fake(['https://hooks.example/*' => Http::response('accepted', 202)]);
        $store = createStoreContext()['store'];
        $subscription = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/orders',
            'signing_secret_encrypted' => 'test-secret',
            'status' => 'active',
        ]);
        $delivery = $subscription->deliveries()->create(['event_id' => (string) Str::uuid(), 'attempt_count' => 0, 'status' => 'pending']);
        $payload = ['order_id' => 42, 'total_amount' => 5000];

        (new DeliverWebhook($delivery, 'order.created', $payload))->handle(app(WebhookService::class));

        expect($delivery->refresh()->status->value)->toBe('success')
            ->and($delivery->attempt_count)->toBe(1)
            ->and($delivery->response_code)->toBe(202)
            ->and($delivery->response_body_snippet)->toBe('accepted');
        Http::assertSent(function ($request) use ($delivery, $payload): bool {
            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $timestamp = (string) ($request->header('X-Platform-Timestamp')[0] ?? '');

            return $request->url() === 'https://hooks.example/orders'
                && $request->body() === $body
                && ctype_digit($timestamp)
                && $request->hasHeader('X-Platform-Event', 'order.created')
                && $request->hasHeader('X-Platform-Delivery-Id', $delivery->event_id)
                && $request->hasHeader('X-Platform-Signature', hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret'));
        });
    });

    it('records failures and exposes the required retry schedule', function () {
        Http::fake(['https://hooks.example/*' => Http::response('broken', 500)]);
        $store = createStoreContext()['store'];
        $subscription = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/orders',
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);
        $delivery = $subscription->deliveries()->create(['event_id' => (string) Str::uuid(), 'attempt_count' => 0, 'status' => 'pending']);
        $job = new DeliverWebhook($delivery, 'order.created', ['order_id' => 1]);

        expect(fn () => $job->handle(app(WebhookService::class)))->toThrow(RequestException::class)
            ->and($delivery->refresh()->status->value)->toBe('failed')
            ->and($delivery->attempt_count)->toBe(1)
            ->and($delivery->response_code)->toBe(500)
            ->and($job->tries)->toBe(6)
            ->and($job->backoff)->toBe([60, 300, 1800, 7200, 43200]);
    });

    it('pauses a subscription after five consecutive delivery failures', function () {
        $store = createStoreContext()['store'];
        $subscription = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/orders',
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);
        foreach (range(1, 5) as $attempt) {
            $delivery = $subscription->deliveries()->create([
                'event_id' => (string) Str::uuid(),
                'attempt_count' => 6,
                'status' => 'failed',
                'last_attempt_at' => now(),
            ]);
        }

        app(WebhookService::class)->recordFailure($delivery);

        expect($subscription->refresh()->status->value)->toBe('paused');
    });

    it('encrypts subscription secrets at rest', function () {
        $store = createStoreContext()['store'];
        $subscription = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/orders',
            'signing_secret_encrypted' => 'plain-secret',
            'status' => 'active',
        ]);
        $raw = DB::table('webhook_subscriptions')->where('id', $subscription->id)->value('signing_secret_encrypted');

        expect($raw)->not->toContain('plain-secret')
            ->and($subscription->signing_secret_encrypted)->toBe('plain-secret');
    });
});

describe('domain side effects audit and media', function () {
    it('turns order creation into notification webhook and deduplicated analytics', function () {
        Notification::fake();
        Queue::fake();
        $fixture = paidOrderFixture();
        WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $fixture['store']->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example/orders',
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);

        event(new OrderCreated($fixture['order']));
        event(new OrderCreated($fixture['order']));

        Notification::assertSentOnDemand(OrderLifecycleNotification::class, function (OrderLifecycleNotification $notification, array $channels, object $notifiable) use ($fixture): bool {
            return $notification->type === 'confirmed'
                && $notification->order->is($fixture['order'])
                && ($notifiable->routes['mail'] ?? null) === $fixture['order']->email;
        });
        expect(AnalyticsEvent::withoutGlobalScopes()
            ->where('store_id', $fixture['store']->id)
            ->where('client_event_id', "order:{$fixture['order']->id}:completed")
            ->count())->toBe(1)
            ->and(WebhookDelivery::query()->count())->toBe(2);
        Queue::assertPushed(DeliverWebhook::class, 2);
    });

    it('writes structured audit changes with actor tenant and resource', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);
        $product = Product::factory()->for($context['store'])->create(['title' => 'Before Audit', 'handle' => 'audit-product']);
        /** @var LoggerInterface&MockInterface $auditChannel */
        $auditChannel = Mockery::mock(LoggerInterface::class);
        $auditChannel->shouldReceive('info')->once()->with('product.updated', Mockery::on(function (array $context) use ($product): bool {
            return $context['event'] === 'product.updated'
                && $context['user_id'] !== null
                && $context['store_id'] === $product->store_id
                && $context['resource_type'] === 'product'
                && $context['resource_id'] === $product->id
                && $context['changes']['title'] === ['Before Audit', 'After Audit'];
        }));
        Log::shouldReceive('channel')->once()->with('audit')->andReturn($auditChannel);

        $product->update(['title' => 'After Audit']);
    });

    it('generates every media rendition and deletes all files with the record', function () {
        Storage::fake('public');
        $store = createStoreContext()['store'];
        $product = Product::factory()->for($store)->create();
        $file = UploadedFile::fake()->image('catalog.png', 1200, 800);
        $source = $file->storeAs('media/originals', 'catalog.png', 'public');
        $media = $product->media()->create([
            'type' => 'image',
            'storage_key' => $source,
            'alt_text' => 'Catalog image',
            'position' => 0,
            'status' => 'processing',
        ]);

        (new ProcessMediaUpload($media))->handle();

        expect($media->refresh()->status->value)->toBe('ready')
            ->and($media->width)->toBe(1200)
            ->and($media->height)->toBe(800)
            ->and($media->mime_type)->toBe('image/png')
            ->and($media->byte_size)->toBeGreaterThan(0);
        foreach (['thumbnail', 'small', 'medium', 'large'] as $size) {
            Storage::disk('public')->assertExists("media/{$product->id}/{$media->id}/{$size}.png");
            Storage::disk('public')->assertExists("media/{$product->id}/{$media->id}/{$size}.webp");
        }

        $media->delete();
        Storage::disk('public')->assertMissing($source);
        foreach (['thumbnail', 'small', 'medium', 'large'] as $size) {
            Storage::disk('public')->assertMissing("media/{$product->id}/{$media->id}/{$size}.png");
            Storage::disk('public')->assertMissing("media/{$product->id}/{$media->id}/{$size}.webp");
        }
    });

    it('marks invalid media failed and preserves the original for diagnosis', function () {
        Storage::fake('public');
        $store = createStoreContext()['store'];
        $product = Product::factory()->for($store)->create();
        $file = UploadedFile::fake()->createWithContent('invalid.png', 'not an image');
        $source = $file->storeAs('media/originals', 'invalid.png', 'public');
        $media = $product->media()->create(['type' => 'image', 'storage_key' => $source, 'position' => 0, 'status' => 'processing']);

        expect(fn () => (new ProcessMediaUpload($media))->handle())->toThrow(RuntimeException::class)
            ->and($media->refresh()->status->value)->toBe('failed');
        Storage::disk('public')->assertExists($source);
    });
});

describe('maintenance scheduling and seeded acceptance data', function () {
    it('abandons stale carts releases checkout reservations and leaves fresh carts active', function () {
        $stale = checkoutReadyForPayment();
        DB::table('carts')->where('id', $stale['cart']->id)->update(['updated_at' => now()->subDays(15)]);
        $freshStore = createStoreContext()['store'];
        $fresh = app(CartService::class)->create($freshStore);

        (new CleanupAbandonedCarts)->handle(app(CheckoutService::class));

        expect($stale['cart']->refresh()->status->value)->toBe('abandoned')
            ->and($stale['checkout']->refresh()->status->value)->toBe('expired')
            ->and($stale['variant']->inventoryItem->refresh()->quantity_reserved)->toBe(0)
            ->and($fresh->refresh()->status->value)->toBe('active');
    });

    it('registers all four required recurring jobs', function () {
        Artisan::call('schedule:list');
        $output = Artisan::output();

        expect($output)->toContain('ExpireAbandonedCheckouts')
            ->and($output)->toContain('CleanupAbandonedCarts')
            ->and($output)->toContain('AggregateAnalytics')
            ->and($output)->toContain('CancelUnpaidBankTransferOrders')
            ->and($output)->toContain('*/15 * * * *')
            ->and($output)->toContain('30   0 * * *')
            ->and($output)->toContain('0    1 * * *')
            ->and($output)->toContain('0    2 * * *');
    });

    it('seeds the exact demo tenants credentials catalog and isolation markers', function () {
        $this->seed();
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();
        $admin = User::query()->where('email', 'admin@acme.test')->firstOrFail();
        $customer = Customer::withoutGlobalScopes()->where('store_id', $fashion->id)->where('email', 'customer@acme.test')->firstOrFail();

        expect(Store::query()->count())->toBe(2)
            ->and($fashion->domains()->where('hostname', 'shop.test')->exists())->toBeTrue()
            ->and($fashion->products()->count())->toBe(20)
            ->and($electronics->products()->count())->toBe(5)
            ->and($fashion->products()->where('handle', 'classic-cotton-t-shirt')->exists())->toBeTrue()
            ->and($fashion->products()->where('handle', 'unreleased-winter-jacket')->where('status', 'draft')->exists())->toBeTrue()
            ->and($fashion->products()->where('handle', 'gift-card')->whereHas('variants', fn ($query) => $query->where('requires_shipping', false))->exists())->toBeTrue()
            ->and($electronics->products()->where('handle', 'pro-laptop-15')->exists())->toBeTrue()
            ->and(password_verify('password', $admin->password_hash))->toBeTrue()
            ->and(password_verify('password', $customer->password_hash))->toBeTrue()
            ->and($admin->roleForStore($fashion)?->value)->toBe('owner');
    });
});
