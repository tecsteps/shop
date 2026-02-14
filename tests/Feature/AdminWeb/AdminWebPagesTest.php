<?php

declare(strict_types=1);

use App\Enums\StoreUserRole;
use App\Models\App as PlatformApp;
use App\Models\AppInstallation;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreUser;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

/**
 * @return array{store: Store, user: User, product: Product, collection: Collection, order: Order, customer: Customer, discount: Discount, theme: Theme, page: Page, installation: AppInstallation}
 */
function createAdminFixture(): array
{
    $store = Store::factory()->create([
        'name' => 'Acme Fashion',
        'handle' => 'acme-fashion',
        'default_currency' => 'EUR',
    ]);

    StoreDomain::factory()->create([
        'store_id' => $store->id,
        'hostname' => 'shop.test',
    ]);

    $user = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@acme.test',
        'password_hash' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    StoreUser::query()->create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'role' => StoreUserRole::Owner,
        'created_at' => now(),
    ]);

    $product = Product::factory()->create([
        'store_id' => $store->id,
        'title' => 'Classic Cotton T-Shirt',
        'handle' => 'classic-cotton-t-shirt',
    ]);

    $collection = Collection::factory()->create([
        'store_id' => $store->id,
        'title' => 'T-Shirts',
        'handle' => 't-shirts',
    ]);

    $customer = Customer::factory()->create([
        'store_id' => $store->id,
        'email' => 'customer@acme.test',
    ]);

    $order = Order::factory()->create([
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'order_number' => '#1001',
    ]);

    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'WELCOME10',
    ]);

    $theme = Theme::query()->create([
        'store_id' => $store->id,
        'name' => 'Default',
        'version' => '1.0.0',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $page = Page::query()->create([
        'store_id' => $store->id,
        'title' => 'About',
        'handle' => 'about',
        'body_html' => '<p>About</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $app = PlatformApp::query()->create([
        'name' => 'Inventory Sync',
        'status' => 'active',
        'created_at' => now(),
    ]);

    $installation = AppInstallation::query()->create([
        'store_id' => $store->id,
        'app_id' => $app->id,
        'scopes_json' => ['orders:read'],
        'status' => 'active',
        'installed_at' => now(),
    ]);

    return [
        'store' => $store,
        'user' => $user,
        'product' => $product,
        'collection' => $collection,
        'order' => $order,
        'customer' => $customer,
        'discount' => $discount,
        'theme' => $theme,
        'page' => $page,
        'installation' => $installation,
    ];
}

test('admin login page renders and authenticated admin can login', function (): void {
    $fixture = createAdminFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/admin/login')
        ->assertOk()
        ->assertSee('Sign in');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->post('/admin/login', [
            'email' => $fixture['user']->email,
            'password' => 'password',
        ])
        ->assertRedirect('/admin');

    $this->assertAuthenticated();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/admin')
        ->assertOk()
        ->assertSee('Dashboard');
});

test('admin dashboard renders evaluated title tag', function (): void {
    $fixture = createAdminFixture();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($fixture['user'])
        ->get('/admin')
        ->assertOk()
        ->assertSee('<title>Dashboard | Acme Fashion</title>', false);
});

test('authenticated admin can open core admin pages', function (): void {
    $fixture = createAdminFixture();

    $user = $fixture['user'];

    $paths = [
        '/admin/products',
        '/admin/products/create',
        '/admin/products/'.$fixture['product']->id.'/edit',
        '/admin/inventory',
        '/admin/collections',
        '/admin/collections/create',
        '/admin/collections/'.$fixture['collection']->id.'/edit',
        '/admin/orders',
        '/admin/orders/'.$fixture['order']->id,
        '/admin/customers',
        '/admin/customers/'.$fixture['customer']->id,
        '/admin/discounts',
        '/admin/discounts/create',
        '/admin/discounts/'.$fixture['discount']->id.'/edit',
        '/admin/settings',
        '/admin/settings/shipping',
        '/admin/settings/taxes',
        '/admin/themes',
        '/admin/themes/'.$fixture['theme']->id.'/editor',
        '/admin/pages',
        '/admin/pages/create',
        '/admin/pages/'.$fixture['page']->id.'/edit',
        '/admin/navigation',
        '/admin/apps',
        '/admin/apps/'.$fixture['installation']->id,
        '/admin/developers',
        '/admin/analytics',
        '/admin/search/settings',
    ];

    foreach ($paths as $path) {
        $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
            ->actingAs($user)
            ->get($path)
            ->assertOk();
    }
});

test('authenticated admin can create update and archive a product via admin web forms', function (): void {
    $fixture = createAdminFixture();
    $store = $fixture['store'];
    $user = $fixture['user'];

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->post('/admin/products', [
            'title' => 'Web Hoodie',
            'handle' => '',
            'description_html' => '<p>Comfortable hoodie.</p>',
            'vendor' => 'Acme',
            'product_type' => 'Hoodies',
            'status' => 'active',
            'tags' => 'hoodie, winter',
            'published_at' => now()->format('Y-m-d H:i:s'),
            'price_amount' => 4599,
            'compare_at_amount' => 5599,
            'currency' => 'eur',
            'requires_shipping' => 1,
        ])
        ->assertRedirect();

    $product = Product::query()
        ->where('store_id', $store->id)
        ->where('title', 'Web Hoodie')
        ->first();

    expect($product)->toBeInstanceOf(Product::class);
    expect($product?->handle)->toBe('web-hoodie');

    $variant = ProductVariant::query()
        ->where('product_id', $product?->id)
        ->where('is_default', true)
        ->first();

    expect($variant)->toBeInstanceOf(ProductVariant::class);
    expect($variant?->price_amount)->toBe(4599);
    expect($variant?->currency)->toBe('EUR');

    $inventoryItem = InventoryItem::query()
        ->where('store_id', $store->id)
        ->where('variant_id', $variant?->id)
        ->first();

    expect($inventoryItem)->toBeInstanceOf(InventoryItem::class);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->put('/admin/products/'.$product?->id, [
            'title' => 'Web Hoodie Updated',
            'handle' => 'web-hoodie-updated',
            'description_html' => '<p>Updated details.</p>',
            'vendor' => 'Acme Updated',
            'product_type' => 'Outerwear',
            'status' => 'draft',
            'tags' => 'updated, featured',
            'published_at' => '',
            'price_amount' => 3999,
            'compare_at_amount' => '',
            'currency' => 'USD',
            'requires_shipping' => 0,
        ])
        ->assertRedirect('/admin/products/'.$product?->id.'/edit');

    $product?->refresh();
    $variant?->refresh();

    expect($product?->title)->toBe('Web Hoodie Updated');
    expect($product?->status->value ?? $product?->status)->toBe('draft');
    expect($variant?->price_amount)->toBe(3999);
    expect($variant?->currency)->toBe('USD');
    expect($variant?->requires_shipping)->toBeFalse();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->delete('/admin/products/'.$product?->id)
        ->assertRedirect('/admin/products');

    $product?->refresh();

    expect($product?->status->value ?? $product?->status)->toBe('archived');
});

test('product web form validates required fields', function (): void {
    $fixture = createAdminFixture();
    $user = $fixture['user'];

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->post('/admin/products', [
            'title' => '',
            'status' => 'active',
            'price_amount' => -1,
            'currency' => 'EU',
        ])
        ->assertSessionHasErrors(['title', 'price_amount', 'currency']);
});

test('authenticated admin can create update and delete a collection via admin web forms', function (): void {
    $fixture = createAdminFixture();
    $store = $fixture['store'];
    $user = $fixture['user'];

    $firstProduct = Product::factory()->create(['store_id' => $store->id]);
    $secondProduct = Product::factory()->create(['store_id' => $store->id]);
    $thirdProduct = Product::factory()->create(['store_id' => $store->id]);
    $outsideStoreProduct = Product::factory()->create();

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->post('/admin/collections', [
            'title' => 'Seasonal Picks',
            'handle' => '',
            'description_html' => '<p>Top picks.</p>',
            'type' => 'manual',
            'status' => 'active',
            'product_ids' => $firstProduct->id.', '.$secondProduct->id.', '.$outsideStoreProduct->id,
        ])
        ->assertRedirect();

    $collection = Collection::query()
        ->where('store_id', $store->id)
        ->where('title', 'Seasonal Picks')
        ->first();

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection?->handle)->toBe('seasonal-picks');

    $linkedProductIds = $collection?->products()->pluck('products.id')->map(static fn (mixed $id): int => (int) $id)->all() ?? [];
    expect($linkedProductIds)->toEqualCanonicalizing([$firstProduct->id, $secondProduct->id]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->put('/admin/collections/'.$collection?->id, [
            'title' => 'Seasonal Picks Updated',
            'handle' => 'seasonal-picks-updated',
            'description_html' => '<p>Updated picks.</p>',
            'type' => 'automated',
            'status' => 'draft',
            'product_ids' => $secondProduct->id.', '.$thirdProduct->id,
        ])
        ->assertRedirect('/admin/collections/'.$collection?->id.'/edit');

    $collection?->refresh();

    expect($collection?->title)->toBe('Seasonal Picks Updated');
    expect($collection?->type->value ?? $collection?->type)->toBe('automated');
    expect($collection?->status->value ?? $collection?->status)->toBe('draft');

    $linkedProductIds = $collection?->products()->pluck('products.id')->map(static fn (mixed $id): int => (int) $id)->all() ?? [];
    expect($linkedProductIds)->toEqualCanonicalizing([$secondProduct->id, $thirdProduct->id]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->delete('/admin/collections/'.$collection?->id)
        ->assertRedirect('/admin/collections');

    $this->assertDatabaseMissing('collections', ['id' => $collection?->id]);
});

test('authenticated admin can create update and delete a discount via admin web forms', function (): void {
    $fixture = createAdminFixture();
    $store = $fixture['store'];
    $user = $fixture['user'];

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->post('/admin/discounts', [
            'type' => 'code',
            'code' => 'save15',
            'value_type' => 'percent',
            'value_amount' => 15,
            'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'ends_at' => '',
            'usage_limit' => 100,
            'usage_count' => 0,
            'rules_json' => '{"minimum_subtotal":1000}',
            'status' => 'active',
        ])
        ->assertRedirect();

    $discount = Discount::query()
        ->where('store_id', $store->id)
        ->where('code', 'SAVE15')
        ->first();

    expect($discount)->toBeInstanceOf(Discount::class);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->put('/admin/discounts/'.$discount?->id, [
            'type' => 'automatic',
            'code' => '',
            'value_type' => 'fixed',
            'value_amount' => 700,
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'usage_limit' => '',
            'usage_count' => 5,
            'rules_json' => '{"minimum_subtotal":5000}',
            'status' => 'disabled',
        ])
        ->assertRedirect('/admin/discounts/'.$discount?->id.'/edit');

    $discount?->refresh();

    expect($discount?->type->value ?? $discount?->type)->toBe('automatic');
    expect($discount?->code)->toBeNull();
    expect($discount?->value_amount)->toBe(700);
    expect($discount?->status->value ?? $discount?->status)->toBe('disabled');
    expect($discount?->rules_json)->toBe(['minimum_subtotal' => 5000]);

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->delete('/admin/discounts/'.$discount?->id)
        ->assertRedirect('/admin/discounts');

    $this->assertDatabaseMissing('discounts', ['id' => $discount?->id]);
});

test('authenticated admin can create update and delete a page via admin web forms', function (): void {
    $fixture = createAdminFixture();
    $store = $fixture['store'];
    $user = $fixture['user'];

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->post('/admin/pages', [
            'title' => 'Shipping Policy',
            'handle' => '',
            'body_html' => '<p>Ships in 2 days.</p>',
            'status' => 'published',
            'published_at' => now()->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect();

    $page = Page::query()
        ->where('store_id', $store->id)
        ->where('title', 'Shipping Policy')
        ->first();

    expect($page)->toBeInstanceOf(Page::class);
    expect($page?->handle)->toBe('shipping-policy');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->put('/admin/pages/'.$page?->id, [
            'title' => 'Shipping Policy Updated',
            'handle' => 'shipping-policy-updated',
            'body_html' => '<p>Ships in 1 day.</p>',
            'status' => 'draft',
            'published_at' => '',
        ])
        ->assertRedirect('/admin/pages/'.$page?->id.'/edit');

    $page?->refresh();

    expect($page?->title)->toBe('Shipping Policy Updated');
    expect($page?->handle)->toBe('shipping-policy-updated');
    expect($page?->status->value ?? $page?->status)->toBe('draft');

    $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->actingAs($user)
        ->delete('/admin/pages/'.$page?->id)
        ->assertRedirect('/admin/pages');

    $this->assertDatabaseMissing('pages', ['id' => $page?->id]);
});
