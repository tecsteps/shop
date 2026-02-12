<?php

use App\Livewire\Admin\Products\Create;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupAdminWithStore(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the create product page', function () {
    [$user, $store] = setupAdminWithStore();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertSee('Create product')
        ->assertSuccessful();
});

test('it auto-generates handle from title', function () {
    [$user, $store] = setupAdminWithStore();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'My Cool Product')
        ->assertSet('handle', 'my-cool-product');
});

test('it creates a product with default variant and inventory', function () {
    [$user, $store] = setupAdminWithStore();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'New Test Product')
        ->set('descriptionHtml', '<p>A test product.</p>')
        ->set('productType', 'Shirts')
        ->set('vendor', 'TestVendor')
        ->set('tags', 'new, sale')
        ->set('status', 'draft')
        ->set('price', '29.99')
        ->set('compareAtPrice', '39.99')
        ->set('sku', 'SKU-TEST-001')
        ->set('barcode', '1234567890123')
        ->set('trackQuantity', true)
        ->set('quantity', 50)
        ->call('save')
        ->assertDispatched('toast')
        ->assertRedirect();

    $product = Product::where('title', 'New Test Product')->first();

    expect($product)->not->toBeNull()
        ->and($product->store_id)->toBe($store->id)
        ->and($product->handle)->toBe('new-test-product')
        ->and($product->description_html)->toBe('<p>A test product.</p>')
        ->and($product->product_type)->toBe('Shirts')
        ->and($product->vendor)->toBe('TestVendor')
        ->and($product->tags)->toBe(['new', 'sale'])
        ->and($product->status->value)->toBe('draft');

    $variant = $product->variants()->where('is_default', true)->first();

    expect($variant)->not->toBeNull()
        ->and($variant->price_amount)->toBe(2999)
        ->and($variant->compare_at_amount)->toBe(3999)
        ->and($variant->sku)->toBe('SKU-TEST-001')
        ->and($variant->barcode)->toBe('1234567890123');

    $inventoryItem = $variant->inventoryItem;

    expect($inventoryItem)->not->toBeNull()
        ->and($inventoryItem->quantity_on_hand)->toBe(50)
        ->and($inventoryItem->store_id)->toBe($store->id);
});

test('it validates required fields', function () {
    [$user, $store] = setupAdminWithStore();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('it converts price to cents correctly', function () {
    [$user, $store] = setupAdminWithStore();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'Penny Product')
        ->set('price', '10.50')
        ->call('save')
        ->assertRedirect();

    $product = Product::where('title', 'Penny Product')->first();
    $variant = $product->variants()->where('is_default', true)->first();

    expect($variant->price_amount)->toBe(1050);
});

test('it creates product without inventory when tracking is disabled', function () {
    [$user, $store] = setupAdminWithStore();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('title', 'No Inventory Product')
        ->set('trackQuantity', false)
        ->call('save')
        ->assertRedirect();

    $product = Product::where('title', 'No Inventory Product')->first();
    $variant = $product->variants()->where('is_default', true)->first();

    expect($variant->inventoryItem)->toBeNull();
});
