<?php

use App\Models\Organization;
use App\Models\Product;
use App\Models\Store;
use App\Services\HandleGenerator as ServicesHandleGenerator;
use App\Support\HandleGenerator as SupportHandleGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function handleGeneratorClass(): string
{
    if (class_exists(SupportHandleGenerator::class)) {
        return SupportHandleGenerator::class;
    }

    if (class_exists(ServicesHandleGenerator::class)) {
        return ServicesHandleGenerator::class;
    }

    test()->fail('HandleGenerator is expected in App\\Support or App\\Services.');

    return ServicesHandleGenerator::class;
}

function handleGeneratorService(): object
{
    $class = handleGeneratorClass();

    $service = app($class);

    expect(method_exists($service, 'generate'))
        ->toBeTrue('HandleGenerator must expose a generate(...) method.');

    return $service;
}

function handleGeneratorCreateStore(string $suffix): Store
{
    $organization = Organization::query()->create([
        'name' => 'Org '.$suffix,
        'billing_email' => 'billing+'.$suffix.'@example.test',
    ]);

    return Store::query()->create([
        'organization_id' => $organization->id,
        'name' => 'Store '.$suffix,
        'handle' => 'store-'.$suffix,
        'status' => 'active',
        'default_currency' => 'EUR',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ]);
}

function handleGeneratorCreateProduct(Store $store, string $handle, string $title = 'Sample Product'): Product
{
    return Product::query()->create([
        'store_id' => $store->id,
        'title' => $title,
        'handle' => $handle,
        'status' => 'active',
        'description_html' => '<p>Fixture</p>',
        'vendor' => 'Fixture Vendor',
        'product_type' => 'Fixture Type',
        'tags' => [],
        'published_at' => now(),
    ]);
}

test('generates a slug from title when no collision exists', function (): void {
    $store = handleGeneratorCreateStore('slug-source');
    $service = handleGeneratorService();

    $handle = $service->generate('My Amazing Product', 'products', $store->id);

    expect($handle)->toBe('my-amazing-product');
});

test('appends incrementing numeric suffixes on collisions', function (): void {
    $store = handleGeneratorCreateStore('collision');
    handleGeneratorCreateProduct($store, 't-shirt', 'T-Shirt');
    handleGeneratorCreateProduct($store, 't-shirt-1', 'T-Shirt');

    $service = handleGeneratorService();
    $handle = $service->generate('T Shirt', 'products', $store->id);

    expect($handle)->toBe('t-shirt-2');
});

test('scopes uniqueness checks per store', function (): void {
    $storeA = handleGeneratorCreateStore('store-a');
    $storeB = handleGeneratorCreateStore('store-b');
    handleGeneratorCreateProduct($storeA, 'classic-hoodie', 'Classic Hoodie');

    $service = handleGeneratorService();
    $handleForStoreB = $service->generate('Classic Hoodie', 'products', $storeB->id);

    expect($handleForStoreB)->toBe('classic-hoodie');
});

test('ignores the current record when exclude id is provided', function (): void {
    $store = handleGeneratorCreateStore('exclude-id');
    $product = handleGeneratorCreateProduct($store, 'utility-pants', 'Utility Pants');

    $service = handleGeneratorService();
    $handle = $service->generate('Utility Pants', 'products', $store->id, $product->id);

    expect($handle)->toBe('utility-pants');
});

test('normalizes special characters into url safe handles', function (): void {
    $store = handleGeneratorCreateStore('special');
    $service = handleGeneratorService();

    $handle = $service->generate("Loewe's Fall/Winter 2026", 'products', $store->id);

    expect($handle)
        ->toMatch('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
        ->and($handle)->toContain('2026');
});
