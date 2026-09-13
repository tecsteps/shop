<?php

use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Collections\Index as CollectionsIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Page;
use App\Models\ProductMedia;
use App\Models\Store;
use App\Services\AnalyticsService;
use App\Services\NavigationService;
use App\Tenancy\CurrentStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('P0-1 resolves the storefront store on Livewire updates without pre-bound CurrentStore', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();

    $this->get(storefrontUrl($domain->hostname, '/'))->assertOk();

    app(CurrentStore::class)->forget();
    expect(app(CurrentStore::class)->get())->toBeNull();

    Livewire::test(Home::class)->assertOk()->assertSee($store->name);
});

it('P0-1 resolves the admin store on Livewire updates without pre-bound CurrentStore', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();

    $this->actingAsAdmin($user, $store)->get('/admin')->assertOk();

    app(CurrentStore::class)->forget();
    expect(app(CurrentStore::class)->get())->toBeNull();

    Livewire::test(Dashboard::class)->assertOk();
});

it('P0-2 serves stateless checkout endpoints without a session store', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $this->postJson(storefrontUrl($domain->hostname, '/api/storefront/v1/checkouts'), [])
        ->assertUnprocessable();

    $this->getJson(storefrontUrl($domain->hostname, '/api/storefront/v1/checkouts/999999'))
        ->assertNotFound();
});

it('P0-3 renders storefront pages with compiled Vite assets', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

    expect($manifest)->toHaveKeys(['resources/css/app.css', 'resources/js/app.js']);

    $this->get(storefrontUrl($domain->hostname, '/'))
        ->assertOk()
        ->assertSee('build/assets', false);
});

it('P1-1 finds seeded products via search after migrate fresh seed', function () {
    Storage::fake('public');
    $this->seed();

    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    $total = (int) DB::selectOne('SELECT COUNT(*) AS total FROM products_fts WHERE store_id = ?', [$store->id])->total;

    expect($total)->toBeGreaterThan(0);

    $response = $this->getJson('http://acme-fashion.test/api/storefront/v1/search?q=cotton');

    $response->assertOk();
    expect($response->json('pagination.total'))->toBeGreaterThan(0);
    expect(collect($response->json('results'))->pluck('title')->all())->toContain('Classic Cotton T-Shirt');
});

it('P1-2 caches navigation urls without leaking across hosts', function () {
    ['store' => $storeA] = $this->createStoreContext();
    ['store' => $storeB, 'domain' => $domainB] = $this->createStoreContext();

    $menu = NavigationMenu::factory()->create(['store_id' => $storeA->id, 'handle' => 'main-menu']);
    $page = Page::factory()->create(['store_id' => $storeA->id, 'handle' => 'about', 'status' => 'published']);
    NavigationItem::factory()->create(['menu_id' => $menu->id, 'type' => 'page', 'label' => 'About', 'resource_id' => $page->id]);

    app(CurrentStore::class)->set($storeA);

    URL::forceRootUrl('http://'.$storeA->storeDomains()->first()->hostname);
    $first = app(NavigationService::class)->buildTree($menu->refresh());

    URL::forceRootUrl('http://'.$domainB->hostname);
    $second = app(NavigationService::class)->buildTree($menu->refresh());

    foreach ([$first, $second] as $tree) {
        expect($tree[0]['url'])->toStartWith('/')
            ->and($tree[0]['url'])->not->toContain($domainB->hostname);
    }

    URL::forceRootUrl(config('app.url'));
});

it('P1-3 encodes order numbers in account order links', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);
    Order::factory()->create(['store_id' => $store->id, 'customer_id' => $customer->id, 'order_number' => '#1001']);

    $response = $this->actingAsCustomer($customer)->get(storefrontUrl($domain->hostname, '/account/orders'));

    $response->assertOk();
    $response->assertSee('%231001', false);
    expect($response->getContent())->not->toContain('/account/orders/#1001');

    $this->actingAsCustomer($customer)
        ->get(storefrontUrl($domain->hostname, '/account/orders/%231001'))
        ->assertOk();
});

it('P1-4 shows backorder messaging while keeping add-to-cart enabled', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 0, 'policy' => 'continue']);

    $this->get(storefrontUrl($domain->hostname, '/products/'.$variant->product->handle))
        ->assertOk()
        ->assertSee('Available on backorder')
        ->assertSee('Add to cart')
        ->assertDontSee('Sold out');

    Livewire::test(ProductShow::class, ['handle' => $variant->product->handle])
        ->call('addToCart')
        ->assertDispatched('cart-updated');
});

it('P1-5 seeds placeholder product media with local image files', function () {
    Storage::fake('public');
    $this->seed();

    expect(ProductMedia::query()->count())->toBe(25);

    $media = ProductMedia::query()
        ->whereHas('product', fn ($query) => $query->where('status', 'active')->whereNotNull('published_at'))
        ->firstOrFail();

    expect(Storage::disk('public')->exists($media->storage_key))->toBeTrue();

    $product = $media->product;
    $hostname = $product->store->storeDomains()->where('type', 'storefront')->firstOrFail()->hostname;

    $this->get(storefrontUrl($hostname, '/products/'.$product->handle))
        ->assertOk()
        ->assertSee('/storage/', false);
});

it('P1-6 serves the admin forgot and reset password flow', function () {
    Notification::fake();
    ['user' => $user] = $this->createStoreContext();

    $this->get('/admin/forgot-password')->assertOk()->assertSee('Email password reset link');

    $this->post('/admin/forgot-password', ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);

    $token = Password::broker('users')->createToken($user);

    $this->get('/admin/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk();

    $this->post('/admin/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect(route('admin.login'));

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'new-secure-password'])
        ->assertRedirect('/admin');
});

it('P1-7 counts real orders in the analytics summary, not events', function () {
    ['store' => $store] = $this->createStoreContext();

    AnalyticsDaily::factory()->create([
        'store_id' => $store->id,
        'date' => today()->toDateString(),
        'orders_count' => 99,
        'revenue_amount' => 99900,
        'aov_amount' => 1009,
        'visits_count' => 200,
    ]);
    AnalyticsEvent::factory()->count(5)->create([
        'store_id' => $store->id,
        'type' => 'checkout_completed',
        'occurred_at' => now(),
        'created_at' => now(),
    ]);
    Order::factory()->count(2)->create([
        'store_id' => $store->id,
        'total_amount' => 5000,
        'placed_at' => now(),
    ]);

    $summary = app(AnalyticsService::class)->summary($store, today()->toDateString(), today()->toDateString());

    expect($summary['summary']['orders_count'])->toBe(2)
        ->and($summary['summary']['revenue_amount'])->toBe(10000)
        ->and($summary['daily'][0]['orders_count'])->toBe(2);
});

it('P2-1 links favicons from layouts and ships the files', function () {
    ['domain' => $domain] = $this->createStoreContext();

    expect(file_exists(public_path('favicon.svg')))->toBeTrue()
        ->and(file_exists(public_path('favicon.ico')))->toBeTrue();

    $this->get(storefrontUrl($domain->hostname, '/'))
        ->assertOk()
        ->assertSee('favicon.svg', false);
});

it('P2-2 aligns user-visible labels with the spec contract', function () {
    ['store' => $store, 'domain' => $domain, 'user' => $user] = $this->createStoreContext();

    $this->get('/admin/login')->assertSee('Sign in');
    $this->get(storefrontUrl($domain->hostname, '/account/login'))->assertSee('Log in');

    $this->actingAsAdmin($user, $store);

    Livewire::test(CollectionsIndex::class)->assertSee('Create collection');
    $this->get(route('admin.pages.index'))->assertSee('Create page');
    $this->get(route('admin.settings.index'))->assertSee('Store Settings');
    $this->get(route('admin.settings.taxes'))->assertSee('Tax Settings');

    Livewire::test(AnalyticsIndex::class)
        ->assertSee('Revenue')
        ->assertSee('Visits')
        ->assertSee('Conversion funnel');
});

it('P2-2 features the earliest products on the homepage', function () {
    ['domain' => $domain] = $this->createStoreContext();
    $store = app(CurrentStore::class)->get();
    $first = CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => 'Homepage First Product']);

    foreach (range(1, 8) as $index) {
        CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => "Homepage Filler {$index}"]);
    }

    $this->get(storefrontUrl($domain->hostname, '/'))
        ->assertOk()
        ->assertSee($first->product->title);
});

it('P2-3 keeps the admin session when the customer logs out', function () {
    ['store' => $store, 'domain' => $domain, 'user' => $user] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $this->actingAs($user)
        ->actingAs($customer, 'customer')
        ->withSession(['current_store_id' => $store->id])
        ->post(storefrontUrl($domain->hostname, '/account/logout'))
        ->assertRedirect('/account/login');

    $this->assertGuest('customer');
    $this->assertAuthenticatedAs($user, 'web');
    expect(session()->get('current_store_id'))->toBe($store->id);
});

it('P2-4 redirects customers away from guest auth pages, dashboard, and settings', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $customer = Customer::factory()->create(['store_id' => $store->id]);

    $this->actingAsCustomer($customer);

    $this->get(storefrontUrl($domain->hostname, '/account/login'))->assertRedirectToRoute('customer.account');
    $this->get(storefrontUrl($domain->hostname, '/account/register'))->assertRedirectToRoute('customer.account');
    $this->get('/dashboard')->assertRedirectToRoute('customer.account');
    $this->get('/settings/profile')->assertRedirectToRoute('customer.account');
});

it('P2-5 shows top product titles on the dashboard', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);
    $order = Order::factory()->create(['store_id' => $store->id, 'total_amount' => 2500, 'placed_at' => now()]);
    \App\Models\OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'title_snapshot' => $variant->product->title,
        'quantity' => 1,
        'unit_price_amount' => 2500,
        'total_amount' => 2500,
    ]);

    $this->actingAsAdmin($user, $store)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee($variant->product->title);
});

it('P2-6 documents the production debug default in the env example', function () {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)->toContain('APP_DEBUG=false');
});

it('P2-7 returns 404 for unregistered storefront hostnames', function () {
    $this->get('http://shop.test/')->assertNotFound();
});

it('P2-8 badges exhausted discounts instead of active', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'ALMOSTGONE',
        'status' => 'active',
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);
    $this->actingAsAdmin($user, $store);

    Livewire::test(DiscountsIndex::class)->assertSee('Exhausted');
});
