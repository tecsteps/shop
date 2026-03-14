<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ctx = createStoreContext();
});

it('shows product detail with title and price', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Premium Cotton Tee',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
        'price_amount' => 2999,
        'is_default' => true,
    ]);

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->assertSee('Premium Cotton Tee')
        ->assertSee('29.99');
});

it('shows variant selector with options', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Multi-Option Shirt',
    ]);

    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
        'position' => 0,
    ]);

    $small = ProductOptionValue::factory()->create([
        'product_option_id' => $option->id,
        'value' => 'S',
        'position' => 0,
    ]);

    $large = ProductOptionValue::factory()->create([
        'product_option_id' => $option->id,
        'value' => 'L',
        'position' => 1,
    ]);

    $variantS = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'S',
        'status' => VariantStatus::Active,
        'price_amount' => 2500,
        'is_default' => true,
    ]);
    $variantS->optionValues()->attach($small->id);

    $variantL = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'L',
        'status' => VariantStatus::Active,
        'price_amount' => 2500,
        'is_default' => false,
    ]);
    $variantL->optionValues()->attach($large->id);

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->assertSee('Size')
        ->assertSee('S')
        ->assertSee('L');
});

it('shows sold out message for deny policy with zero inventory', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Sold Out Product',
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
        'price_amount' => 3000,
        'is_default' => true,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Deny,
    ]);

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->assertSee('Out of stock')
        ->assertSee('Sold out');
});

it('shows backorder message for continue policy with zero inventory', function () {
    $product = Product::factory()->active()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Backorder Product',
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => VariantStatus::Active,
        'price_amount' => 3000,
        'is_default' => true,
    ]);

    InventoryItem::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'policy' => InventoryPolicy::Continue,
    ]);

    Livewire::test(ProductShow::class, ['handle' => $product->handle])
        ->assertSee('Available on backorder');
});

it('returns 404 for draft product', function () {
    $product = Product::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'title' => 'Draft Only',
        'status' => ProductStatus::Draft,
    ]);

    $hostname = $this->ctx['domain']->hostname;

    $response = $this->call('GET', 'http://'.$hostname.'/products/'.$product->handle);

    $response->assertNotFound();
});
