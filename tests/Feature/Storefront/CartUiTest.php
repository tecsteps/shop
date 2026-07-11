<?php

use App\Enums\ProductStatus;
use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Livewire\Storefront\Products\Show as ProductShow;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::factory()->create(['default_currency' => 'EUR']);
    app()->instance('current_store', $this->store);
    $this->product = Product::factory()->for($this->store)->create(['title' => 'Classic Tee', 'handle' => 'classic-tee', 'status' => ProductStatus::Active, 'published_at' => now()]);
    $this->variant = ProductVariant::factory()->for($this->product)->default()->create(['price_amount' => 2499]);
    $this->variant->inventoryItem->update(['quantity_on_hand' => 10]);
});

it('adds a selected variant to the session cart', function () {
    Livewire::test(ProductShow::class, ['handle' => 'classic-tee'])
        ->set('quantity', 2)
        ->call('addToCart')
        ->assertDispatched('cart-updated');

    $cartId = session('cart_id');
    expect($cartId)->not->toBeNull();
    $this->assertDatabaseHas('cart_lines', ['cart_id' => $cartId, 'variant_id' => $this->variant->id, 'quantity' => 2]);
});

it('updates and removes cart lines through the cart page', function () {
    Livewire::test(ProductShow::class, ['handle' => 'classic-tee'])->call('addToCart');
    $cartLine = \App\Models\Cart::find(session('cart_id'))->lines()->firstOrFail();

    Livewire::test(CartShow::class)
        ->call('updateQuantity', $cartLine->id, 3)
        ->assertSee('Classic Tee')
        ->call('removeLine', $cartLine->id)
        ->assertSee('Your cart is empty');
});
