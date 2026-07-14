<?php

use App\Contracts\DnsResolver;
use App\Exceptions\UnsafeWebhookTargetException;
use App\Jobs\CancelUnpaidBankTransferOrders;
use App\Jobs\CleanupAbandonedCarts;
use App\Jobs\Concerns\RestoresCurrentStore;
use App\Jobs\DeliverWebhook;
use App\Jobs\ExpireAbandonedCheckouts;
use App\Livewire\Admin\Developers\Index as Developers;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Order;
use App\Models\WebhookSubscription;
use App\Notifications\OrderLifecycleNotification;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\OrderStatusLink;
use App\Services\OutboundDispatcher;
use App\Services\WebhookService;
use App\Services\WebhookTargetValidator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;

describe('webhook SSRF controls', function () {
    it('accepts only HTTPS hosts resolving exclusively to public non-reserved addresses', function () {
        app()->instance(DnsResolver::class, new class implements DnsResolver
        {
            public function resolve(string $hostname): array
            {
                return match ($hostname) {
                    'safe.example.com' => ['8.8.8.8', '2606:4700:4700::1111'],
                    'mixed.example.com' => ['8.8.8.8', '169.254.169.254'],
                    default => [],
                };
            }
        });
        $validator = app(WebhookTargetValidator::class);

        expect($validator->validate('https://safe.example.com/hooks')->url)->toBe('https://safe.example.com/hooks');

        foreach ([
            'http://safe.example.com/hooks',
            'https://localhost/hooks',
            'https://127.0.0.1/hooks',
            'https://169.254.169.254/latest/meta-data',
            'https://192.0.2.1/hooks',
            'https://mixed.example.com/hooks',
            'https://unresolved.example.com/hooks',
            'https://user:pass@safe.example.com/hooks',
        ] as $url) {
            expect(fn () => $validator->validate($url))->toThrow(UnsafeWebhookTargetException::class);
        }
    });

    it('rejects unsafe targets in the admin save flow', function () {
        $context = createStoreContext();
        actingAsAdmin($context['user'], $context['store']);

        Livewire::test(Developers::class)
            ->set('webhookEventType', 'order.created')
            ->set('webhookUrl', 'https://127.0.0.1/internal')
            ->call('saveWebhook')
            ->assertHasErrors('webhookUrl');

        expect(WebhookSubscription::withoutGlobalScopes()->count())->toBe(0);
    });

    it('revalidates before delivery and never sends to a private address', function () {
        Http::fake();
        $store = createStoreContext()['store'];
        $subscription = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://127.0.0.1/internal',
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);
        $delivery = $subscription->deliveries()->create([
            'event_id' => (string) Str::uuid(), 'attempt_count' => 0, 'status' => 'pending',
        ]);

        expect(fn () => (new DeliverWebhook($delivery, 'order.created', ['order_id' => 1]))
            ->handle(app(WebhookService::class)))->toThrow(UnsafeWebhookTargetException::class);
        expect($delivery->refresh()->status->value)->toBe('failed')
            ->and($delivery->attempt_count)->toBe(1);
        Http::assertNothingSent();
    });

    it('does not follow webhook redirects', function () {
        app()->instance(DnsResolver::class, new class implements DnsResolver
        {
            public function resolve(string $hostname): array
            {
                return ['8.8.8.8'];
            }
        });
        Http::fake(['https://hooks.example.com/*' => Http::response('', 302, ['Location' => 'https://127.0.0.1/private'])]);
        $store = createStoreContext()['store'];
        $subscription = WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://hooks.example.com/orders',
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);
        $delivery = $subscription->deliveries()->create([
            'event_id' => (string) Str::uuid(), 'attempt_count' => 0, 'status' => 'pending',
        ]);

        expect(fn () => (new DeliverWebhook($delivery, 'order.created', []))
            ->handle(app(WebhookService::class)))->toThrow(RequestException::class);
        expect($delivery->refresh()->response_code)->toBe(302)
            ->and($delivery->status->value)->toBe('failed');
        Http::assertSentCount(1);
    });
});

describe('post-commit outbound isolation', function () {
    it('runs callbacks only after commit and swallows outbound failures', function () {
        Log::spy();
        $originalEnvironment = app()->environment();
        $originalConnection = config('database.default');
        app()->detectEnvironment(fn (): string => 'local');
        config([
            'database.default' => 'outbound_testing',
            'database.connections.outbound_testing' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
        ]);
        DB::purge('outbound_testing');
        $ran = false;

        try {
            DB::beginTransaction();
            (new OutboundDispatcher)->afterCommit(function () use (&$ran): void {
                $ran = true;
                throw new RuntimeException('mail transport unavailable');
            });
            expect($ran)->toBeFalse();
            DB::commit();
            expect($ran)->toBeTrue();
        } finally {
            DB::purge('outbound_testing');
            config(['database.default' => $originalConnection]);
            app()->detectEnvironment(fn (): string => $originalEnvironment);
        }
    });

    it('isolates a synchronous webhook delivery failure from its caller', function () {
        config(['queue.default' => 'sync']);
        Http::fake();
        $store = createStoreContext()['store'];
        WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => 'order.created',
            'target_url' => 'https://127.0.0.1/internal',
            'signing_secret_encrypted' => 'secret',
            'status' => 'active',
        ]);

        expect(fn () => app(WebhookService::class)->dispatch($store, 'order.created', ['order_id' => 1]))
            ->not->toThrow(Throwable::class);
        expect($store->refresh())->not->toBeNull()
            ->and($store->webhookSubscriptions()->firstOrFail()->deliveries()->firstOrFail()->status->value)->toBe('failed');
        Http::assertNothingSent();
    });
});

describe('optional customer lifecycle notifications', function () {
    it('suppresses fulfillment and refund emails while retaining their webhooks', function () {
        Notification::fake();
        Queue::fake();
        $fixture = paidOrderFixture(quantity: 2);
        $store = $fixture['store'];
        $user = $store->users()->firstOrFail();
        $token = $user->createToken('notification-choice-test', [
            'write-orders',
            'store:'.$store->id,
        ])->plainTextToken;

        foreach (['fulfillment.created', 'order.updated', 'order.refunded', 'refund.created'] as $eventType) {
            WebhookSubscription::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'event_type' => $eventType,
                'target_url' => 'https://hooks.example/'.$eventType,
                'signing_secret_encrypted' => 'secret',
                'status' => 'active',
            ]);
        }

        $base = "/api/admin/v1/stores/{$store->id}/orders/{$fixture['order']->id}";
        $this->withToken($token)->postJson($base.'/fulfillments', [
            'lines' => [$fixture['line']->id => 2],
            'tracking_company' => 'DHL',
            'tracking_number' => 'NO-MAIL-1',
            'notify_customer' => false,
        ])->assertCreated();

        Notification::assertNothingSent();
        expect(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'fulfillment.created')->firstOrFail()->deliveries)->toHaveCount(1)
            ->and(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'order.updated')->firstOrFail()->deliveries->count())->toBeGreaterThanOrEqual(1);

        $this->withToken($token)->postJson($base.'/refunds', [
            'amount' => 1000,
            'reason' => 'No customer email requested',
            'notify_customer' => false,
        ])->assertCreated();

        Notification::assertNothingSent();
        expect(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'order.refunded')->firstOrFail()->deliveries)->toHaveCount(1)
            ->and(WebhookSubscription::withoutGlobalScopes()->where('event_type', 'refund.created')->firstOrFail()->deliveries)->toHaveCount(1);
    });
});

describe('tenant context cleanup', function () {
    it('restores the previous tenant after each recurring cleanup job', function () {
        $previous = createStoreContext()['store'];
        $tenant = createStoreContext()['store'];

        $expiredCart = Cart::factory()->for($tenant)->create();
        Checkout::factory()->for($tenant)->for($expiredCart)->create([
            'status' => 'started', 'expires_at' => now()->subHour(),
        ]);
        $staleCart = Cart::factory()->for($tenant)->create(['updated_at' => now()->subDays(15)]);
        Order::factory()->pendingBankTransfer()->for($tenant)->create([
            'customer_id' => null, 'placed_at' => now()->subDays(8),
        ]);
        bindStore($previous);

        (new ExpireAbandonedCheckouts)->handle(app(CheckoutService::class));
        expect(app('current_store')->is($previous))->toBeTrue();
        (new CleanupAbandonedCarts)->handle(app(CheckoutService::class));
        expect(app('current_store')->is($previous))->toBeTrue()
            ->and($staleCart->refresh()->status->value)->toBe('abandoned');
        (new CancelUnpaidBankTransferOrders)->handle(app(OrderService::class));
        expect(app('current_store')->is($previous))->toBeTrue();
    });

    it('restores or forgets tenant state even when work throws', function () {
        $first = createStoreContext()['store'];
        $second = createStoreContext()['store'];
        $probe = new class
        {
            use RestoresCurrentStore;

            public function fail(mixed $store): void
            {
                $this->restoringCurrentStore(function () use ($store): void {
                    app()->instance('current_store', $store);
                    throw new RuntimeException('failed work');
                });
            }
        };
        bindStore($first);

        expect(fn () => $probe->fail($second))->toThrow(RuntimeException::class, 'failed work');
        expect(app('current_store')->is($first))->toBeTrue();
        app()->forgetInstance('current_store');
        expect(fn () => $probe->fail($second))->toThrow(RuntimeException::class, 'failed work');
        expect(app()->bound('current_store'))->toBeFalse();
    });
});

describe('order status links', function () {
    it('generates a reusable tenant URL accepted by the status API and notification', function () {
        $context = createStoreContext();
        $order = Order::factory()->for($context['store'])->create([
            'customer_id' => null, 'order_number' => '#LINK1001', 'email' => 'guest@example.test',
        ]);
        $links = app(OrderStatusLink::class);
        $url = $links->url($order);

        expect($links->verify($order, $links->token($order)))->toBeTrue()
            ->and($links->verify($order, 'wrong'))->toBeFalse()
            ->and($url)->toStartWith('http://'.$context['domain']->hostname.'/api/storefront/v1/orders/%23LINK1001?token=');
        $this->getJson($url)->assertOk()->assertJsonPath('order_number', '#LINK1001');
        expect((new OrderLifecycleNotification($order, 'confirmed'))->toMail((object) [])->actionUrl)->toBe($url);
    });

    it('exposes the signed status URL on checkout confirmation', function () {
        Queue::fake();
        $context = createStoreContext();
        $cart = Cart::factory()->for($context['store'])->create();
        $order = Order::factory()->for($context['store'])->create(['customer_id' => null]);
        $checkout = Checkout::factory()->completed()->for($context['store'])->for($cart)->create([
            'email' => $order->email, 'totals_json' => ['order_id' => $order->id],
        ]);
        session(['checkout_access.'.$checkout->id => true]);
        $url = app(OrderStatusLink::class)->url($order);

        Livewire::test(Confirmation::class, ['checkoutId' => $checkout->id])
            ->assertSet('orderStatusUrl', $url)
            ->assertSee('Track order');
    });
});
