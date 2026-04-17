<?php

use App\Livewire\Storefront\Cart\Show;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\CartService;
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

function seedCartWithOneLine(Store $store): array
{
    $product = Product::factory()->for($store)->create(['title' => 'Ceramic Mug']);
    $variant = ProductVariant::factory()->for($product)->create(['price_amount' => 1800]);
    InventoryItem::factory()
        ->for($store)
        ->for($variant, 'variant')
        ->create(['quantity_on_hand' => 10]);

    $cart = CartSession::getOrCreate($store);
    app(CartService::class)->addLine($cart, $variant->id, 1);

    return [$cart, $variant];
}

it('renders an empty cart state', function (): void {
    Livewire::test(Show::class)
        ->assertStatus(200)
        ->assertSee('Your cart is empty');
});

it('renders cart lines with totals', function (): void {
    [$cart, $variant] = seedCartWithOneLine($this->store);

    Livewire::test(Show::class)
        ->assertStatus(200)
        ->assertSee('Ceramic Mug');
});

it('updates the quantity of a cart line', function (): void {
    [$cart, $variant] = seedCartWithOneLine($this->store);
    $line = $cart->lines()->first();

    Livewire::test(Show::class)
        ->call('updateQty', $line->id, 3)
        ->assertHasNoErrors();

    expect((int) $cart->lines()->first()->quantity)->toBe(3);
});

it('removes a cart line', function (): void {
    [$cart, $variant] = seedCartWithOneLine($this->store);
    $line = $cart->lines()->first();

    Livewire::test(Show::class)
        ->call('removeLine', $line->id);

    expect($cart->lines()->count())->toBe(0);
});
