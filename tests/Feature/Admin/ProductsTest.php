<?php

use App\Enums\ProductStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Product;
use App\Models\StoreUser;
use App\Models\User;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists products scoped to the current store', function () {
    $product = Product::factory()->create(['store_id' => $this->store->getKey(), 'title' => 'Store One Tee']);
    ['store' => $otherStore] = $this->createStoreContext();
    Product::factory()->create(['store_id' => $otherStore->getKey(), 'title' => 'Other Store Tee']);

    $this->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Store One Tee')
        ->assertDontSee('Other Store Tee');

    expect($product->store_id)->toBe($this->store->getKey());
});

it('filters products by search and status', function () {
    Product::factory()->create(['store_id' => $this->store->getKey(), 'title' => 'Red Hoodie', 'status' => ProductStatus::Active]);
    Product::factory()->create(['store_id' => $this->store->getKey(), 'title' => 'Blue Hoodie', 'status' => ProductStatus::Draft]);

    Livewire::test(Index::class)
        ->set('search', 'Red')
        ->assertSee('Red Hoodie')
        ->assertDontSee('Blue Hoodie')
        ->set('search', '')
        ->set('statusFilter', 'draft')
        ->assertSee('Blue Hoodie')
        ->assertDontSee('Red Hoodie');
});

it('creates a product through the shared form', function () {
    Livewire::test(Form::class)
        ->set('title', 'Fresh Sneakers')
        ->set('status', 'active')
        ->set('vendor', 'Acme')
        ->set('variants.0.sku', 'TEST-SNK-01')
        ->set('variants.0.price', '79.99')
        ->set('variants.0.quantity', 10)
        ->call('save')
        ->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('store_id', $this->store->getKey())->where('title', 'Fresh Sneakers')->firstOrFail();

    expect($product->status)->toBe(ProductStatus::Active)
        ->and($product->productVariants()->count())->toBe(1)
        ->and($product->productVariants()->first()->price_amount)->toBe(7999)
        ->and($product->productVariants()->first()->inventoryItem->quantity_on_hand)->toBe(10);
});

it('edits a product through the shared form', function () {
    $variant = CommerceTestHelper::purchasableVariant($this->store);
    $product = $variant->product;

    Livewire::test(Form::class, ['product' => $product])
        ->set('title', 'New Title')
        ->call('save')
        ->assertDispatched('toast');

    expect($product->refresh()->title)->toBe('New Title');
});

it('returns 404 when editing a product from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $product = Product::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.products.edit', $product))->assertNotFound();
});

it('archives products via bulk action', function () {
    $product = Product::factory()->create(['store_id' => $this->store->getKey(), 'status' => ProductStatus::Active]);

    Livewire::test(Index::class)
        ->set('selectedIds', [$product->getKey()])
        ->call('bulkArchive')
        ->assertDispatched('toast');

    expect($product->refresh()->status)->toBe(ProductStatus::Archived);
});

it('prevents support users from mutating products', function () {
    $support = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $this->store->getKey(),
        'user_id' => $support->getKey(),
        'role' => StoreUserRole::Support,
    ]);
    $this->actingAsAdmin($support, $this->store);
    $product = Product::factory()->create(['store_id' => $this->store->getKey(), 'status' => ProductStatus::Active]);

    Livewire::test(Index::class)
        ->set('selectedIds', [$product->getKey()])
        ->call('bulkArchive')
        ->assertForbidden();

    expect($product->refresh()->status)->toBe(ProductStatus::Active);
});

it('allows support users to view the product list', function () {
    $support = User::factory()->create();
    StoreUser::factory()->create([
        'store_id' => $this->store->getKey(),
        'user_id' => $support->getKey(),
        'role' => StoreUserRole::Support,
    ]);
    $this->actingAsAdmin($support, $this->store);

    $this->get(route('admin.products.index'))->assertOk();
});
