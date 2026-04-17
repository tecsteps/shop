<?php

use App\Enums\ProductStatus;
use App\Enums\StoreDomainType;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeStorefront(string $hostname = 'shop.test'): Store
{
    $org = Organization::query()->create([
        'name' => 'Shop Holdings',
        'billing_email' => 'billing@'.$hostname,
    ]);

    $store = Store::factory()->create([
        'organization_id' => $org->getKey(),
        'handle' => 'shop',
        'name' => 'Shop',
    ]);

    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => $hostname,
        'type' => StoreDomainType::Storefront->value,
        'is_primary' => 1,
    ]);

    StoreSettings::query()->updateOrCreate(
        ['store_id' => $store->getKey()],
        ['settings_json' => '{}'],
    );

    return $store;
}

it('renders the search page and shows matching products', function () {
    $store = makeStorefront();

    $product = Product::factory()->create([
        'store_id' => $store->getKey(),
        'title' => 'Searchable Hat',
        'status' => ProductStatus::Active->value,
        'published_at' => now(),
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->getKey(),
        'price_amount' => 1999,
        'is_default' => 1,
    ]);

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/search?q=Searchable');

    $response->assertOk();
    $response->assertSee('Searchable Hat');
    $response->assertSee('19.99');
});

it('renders the search page with no results message when nothing matches', function () {
    makeStorefront();

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/search?q=zzzxxxnomatch');

    $response->assertOk();
    $response->assertSee('No products matched your search.');
});

it('renders the empty search page with no query', function () {
    makeStorefront();

    $response = $this->withServerVariables(['HTTP_HOST' => 'shop.test'])
        ->get('/search');

    $response->assertOk();
    $response->assertSee('Search');
});
