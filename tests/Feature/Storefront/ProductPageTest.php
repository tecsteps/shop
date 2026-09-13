<?php

use App\Livewire\Storefront\Products\Show;
use App\Services\ProductService;
use Livewire\Livewire;
use Tests\Support\CatalogTestHelper;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the product page with price and add to cart', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    $response = $this->get(storefrontUrl($domain->hostname, '/products/'.$variant->product->handle));

    $response->assertOk();
    $response->assertSee($variant->product->title);
    $response->assertSee('Add to cart');
    $response->assertSee('25.00');
});

it('returns 404 for unknown, draft, or foreign products', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    ['store' => $otherStore] = $this->createStoreContext();
    $foreign = CommerceTestHelper::purchasableVariant($otherStore);
    $draft = CommerceTestHelper::purchasableVariant($store, productOverrides: ['title' => 'Drafted Product']);
    app(App\Tenancy\CurrentStore::class)->set($store);
    $draft->product->update(['status' => App\Enums\ProductStatus::Draft]);

    $foreignHandle = App\Models\Product::withoutGlobalScopes()->find($foreign->product_id)->handle;

    $this->get(storefrontUrl($domain->hostname, '/products/nope'))->assertNotFound();
    $this->get(storefrontUrl($domain->hostname, '/products/'.$foreignHandle))->assertNotFound();
    $this->get(storefrontUrl($domain->hostname, '/products/'.$draft->product->handle))->assertNotFound();
});

it('selects variants and adds the chosen variant to cart', function () {
    ['store' => $store] = $this->createStoreContext();

    $product = app(ProductService::class)->create($store, CatalogTestHelper::productPayload([
        'title' => 'Sized Shirt',
        'variants' => [
            [
                'sku' => 'SHIRT-S', 'price_amount' => 2000, 'is_default' => true, 'position' => 1, 'status' => 'active',
                'option_values' => [['option_name' => 'Size', 'value' => 'Small']],
                'inventory' => ['quantity_on_hand' => 10, 'policy' => 'deny'],
            ],
            [
                'sku' => 'SHIRT-L', 'price_amount' => 3000, 'is_default' => false, 'position' => 2, 'status' => 'active',
                'option_values' => [['option_name' => 'Size', 'value' => 'Large']],
                'inventory' => ['quantity_on_hand' => 10, 'policy' => 'deny'],
            ],
        ],
    ]));
    app(ProductService::class)->transitionStatus($product->refresh(), App\Enums\ProductStatus::Active);

    Livewire::test(Show::class, ['handle' => $product->handle])
        ->assertSee('20.00')
        ->set('selectedOptions', ['Size' => 'Large'])
        ->assertSee('30.00')
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertDispatched('cart-updated');

    expect(App\Models\CartLine::query()->where('quantity', 2)->exists())->toBeTrue();
});

it('shows out of stock messaging for unavailable variants', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 0]);

    $response = $this->get(storefrontUrl($domain->hostname, '/products/'.$variant->product->handle));

    $response->assertOk();
    $response->assertSee('Out of stock');
    $response->assertSee('Sold out');
});

it('shows low stock messaging when few items remain', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 3]);

    Livewire::test(Show::class, ['handle' => $variant->product->handle])
        ->assertSee('Only 3 left in stock');
});
