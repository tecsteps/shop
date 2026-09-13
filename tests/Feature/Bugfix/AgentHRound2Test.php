<?php

use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Storefront\CartDrawer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Services\AnalyticsService;
use App\Services\ProductService;
use App\Tenancy\CurrentStore;
use Livewire\Livewire;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('BUG1 updates 0-based seeded options to 1-based positions without a unique violation', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = app(ProductService::class)->create($store, CatalogTestHelper::productPayload([
        'options' => [
            ['name' => 'Size', 'position' => 1],
            ['name' => 'Color', 'position' => 2],
        ],
    ]));

    // Mimic the seeders, which store options 0-based while form/API payloads
    // are 1-based. Naive in-place re-positioning collides mid-update with
    // UNIQUE(product_id, position) (SQLSTATE 23000, ProductService syncOptions).
    $product->productOptions()->where('name', 'Size')->update(['position' => 0]);
    $product->productOptions()->where('name', 'Color')->update(['position' => 1]);

    $updated = app(ProductService::class)->update($product->fresh(), [
        'options' => [
            ['name' => 'Size', 'position' => 1, 'values' => ['XS', 'Small']],
            ['name' => 'Color', 'position' => 2, 'values' => ['Red']],
        ],
    ]);

    expect($updated->productOptions()->orderBy('position')->pluck('position')->all())->toBe([1, 2])
        ->and($updated->productOptions()->orderBy('position')->pluck('name')->all())->toBe(['Size', 'Color']);

    $size = $updated->productOptions()->where('name', 'Size')->firstOrFail();

    expect($size->productOptionValues()->orderBy('position')->pluck('value')->all())->toBe(['XS', 'Small']);
});

it('BUG1 saves the admin product form for seeded options with bound and unbound CurrentStore', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $this->actingAsAdmin($user, $store);

    $product = app(ProductService::class)->create($store, CatalogTestHelper::productPayload([
        'options' => [
            ['name' => 'Size', 'position' => 1, 'values' => ['S', 'M']],
            ['name' => 'Color', 'position' => 2, 'values' => ['Red', 'Blue']],
        ],
    ]));
    $product->productOptions()->where('name', 'Size')->update(['position' => 0]);
    $product->productOptions()->where('name', 'Color')->update(['position' => 1]);

    Livewire::test(ProductForm::class, ['product' => $product->fresh()])
        ->set('title', 'Bound Save Title')
        ->call('save')
        ->assertDispatched('toast');

    expect($product->refresh()->title)->toBe('Bound Save Title')
        ->and($product->productOptions()->orderBy('position')->pluck('name')->all())->toBe(['Size', 'Color']);

    app(CurrentStore::class)->forget();
    expect(app(CurrentStore::class)->get())->toBeNull();

    Livewire::test(ProductForm::class, ['product' => $product->fresh()])
        ->set('title', 'Unbound Save Title')
        ->call('save')
        ->assertDispatched('toast');

    expect($product->refresh()->title)->toBe('Unbound Save Title')
        ->and($product->productOptions()->orderBy('position')->pluck('position')->all())->toBe([1, 2]);
});

it('BUG2 creates products and discounts via Livewire without a pre-bound CurrentStore', function () {
    ['store' => $store, 'user' => $user] = $this->createStoreContext();
    $this->actingAsAdmin($user, $store);

    app(CurrentStore::class)->forget();
    expect(app(CurrentStore::class)->get())->toBeNull();

    Livewire::test(ProductForm::class)
        ->set('title', 'Unbound Product')
        ->set('status', 'draft')
        ->set('variants.0.sku', 'UNBOUND-01')
        ->set('variants.0.price', '10.00')
        ->set('variants.0.quantity', 5)
        ->call('save')
        ->assertRedirect(route('admin.products.index'));

    expect(Product::query()->where('store_id', $store->getKey())->where('title', 'Unbound Product')->exists())->toBeTrue();

    app(CurrentStore::class)->forget();
    expect(app(CurrentStore::class)->get())->toBeNull();

    Livewire::test(DiscountForm::class)
        ->set('type', 'code')
        ->set('code', 'UNBOUND10')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    expect(Discount::query()->where('store_id', $store->getKey())->where('code', 'UNBOUND10')->exists())->toBeTrue();
});

it('BUG3 links the svg favicon and never the ico file that Herd serves as 404', function () {
    ['store' => $store, 'domain' => $domain, 'user' => $user] = $this->createStoreContext();

    expect(file_exists(public_path('favicon.svg')))->toBeTrue()
        ->and(file_exists(public_path('favicon.ico')))->toBeTrue();

    $this->get(storefrontUrl($domain->hostname, '/'))
        ->assertOk()
        ->assertSee('favicon.svg', false)
        ->assertDontSee('favicon.ico', false);

    $this->actingAsAdmin($user, $store)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('favicon.svg', false)
        ->assertDontSee('favicon.ico', false);
});

it('L1 keeps the cart drawer closed for cart-page updates but opens it elsewhere', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $drawer = new CartDrawer;
    request()->headers->set('referer', storefrontUrl($domain->hostname, '/cart'));
    $drawer->onCartUpdated();

    expect($drawer->open)->toBeFalse();

    $drawer = new CartDrawer;
    request()->headers->set('referer', storefrontUrl($domain->hostname, '/'));
    $drawer->onCartUpdated();

    expect($drawer->open)->toBeTrue();

    $drawer = new CartDrawer;
    $drawer->open = true;
    request()->headers->set('referer', storefrontUrl($domain->hostname, '/cart'));
    $drawer->onCartUpdated();

    expect($drawer->open)->toBeTrue();
});

it('L2 renders gallery images with relative storage urls', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);
    $product = $variant->product;
    $product->productMedia()->create([
        'storage_key' => 'products/demo.jpg',
        'alt_text' => null,
        'position' => 0,
    ]);

    $response = $this->get(storefrontUrl($domain->hostname, '/products/'.$product->handle));

    $response->assertOk();
    $response->assertSee('src="/storage/products/demo.jpg"', false);
    expect($response->getContent())->not->toContain('http://'.$domain->hostname.'/storage/')
        ->and($response->getContent())->not->toContain('http://shop.test/storage/');
});

it('L3 groups top products by product when snapshots carry variant suffixes', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => 'Grouped Tee']);

    $order = Order::factory()->create(['store_id' => $store->id, 'total_amount' => 5000, 'placed_at' => now()]);

    foreach (['Grouped Tee', 'Grouped Tee (M / White)'] as $snapshot) {
        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'title_snapshot' => $snapshot,
            'quantity' => 1,
            'unit_price_amount' => 2500,
            'total_amount' => 2500,
        ]);
    }

    $top = app(AnalyticsService::class)->topProductsByRevenue($store, today()->toDateString(), today()->toDateString());

    expect($top)->toHaveCount(1)
        ->and($top[0]['title'])->toBe('Grouped Tee')
        ->and($top[0]['units_sold'])->toBe(2)
        ->and($top[0]['revenue_amount'])->toBe(5000);
});
