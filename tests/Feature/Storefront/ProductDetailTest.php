<?php

use App\Livewire\Storefront\Products\Show;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\CartSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->store = Store::factory()->create();
    app()->instance('current_store', $this->store);
});

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('renders a product with its variants', function (): void {
    $product = Product::factory()->for($this->store)->create([
        'title' => 'Wool Coat',
        'handle' => 'wool-coat',
    ]);
    ProductVariant::factory()->for($product)->create(['price_amount' => 12999]);

    Livewire::test(Show::class, ['handle' => 'wool-coat'])
        ->assertStatus(200)
        ->assertSee('Wool Coat')
        ->assertSee('Add to cart');
});

it('adds the selected variant to the cart', function (): void {
    $product = Product::factory()->for($this->store)->create([
        'title' => 'Canvas Bag',
        'handle' => 'canvas-bag',
    ]);
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 3200]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($variant, 'variant')
        ->create(['quantity_on_hand' => 5]);

    Livewire::test(Show::class, ['handle' => 'canvas-bag'])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertHasNoErrors();

    $cart = CartSession::current();
    expect($cart)->not->toBeNull()
        ->and($cart->lines()->count())->toBe(1)
        ->and((int) $cart->lines()->first()->quantity)->toBe(2);
});

it('shows an error when inventory is insufficient', function (): void {
    $product = Product::factory()->for($this->store)->create(['handle' => 'rare-item']);
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 1000]);
    InventoryItem::factory()
        ->for($this->store)
        ->for($variant, 'variant')
        ->create(['quantity_on_hand' => 1]);

    Livewire::test(Show::class, ['handle' => 'rare-item'])
        ->set('quantity', 5)
        ->call('addToCart')
        ->assertHasErrors('cart');
});
