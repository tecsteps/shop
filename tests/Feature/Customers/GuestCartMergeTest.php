<?php

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('merges the guest cart into the customer cart on login', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'cart-merge.test']);

    $customer = Customer::withoutGlobalScopes()->create([
        'store_id' => $ctx['store']->id,
        'email' => 'merge@example.com',
        'password' => Hash::make('password'),
        'state' => 'active',
    ]);

    $product = Product::factory()->create(['store_id' => $ctx['store']->id]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price_amount' => 1500,
        'currency' => 'EUR',
        'is_default' => true,
    ]);
    InventoryItem::factory()->create([
        'store_id' => $ctx['store']->id,
        'variant_id' => $variant->id,
        'quantity_on_hand' => 50,
    ]);

    $guest = Cart::query()->create([
        'store_id' => $ctx['store']->id,
        'customer_id' => null,
        'currency' => 'EUR',
        'status' => CartStatus::Active,
        'subtotal_amount' => 1500,
        'total_amount' => 1500,
        'cart_version' => 1,
    ]);

    CartLine::query()->create([
        'cart_id' => $guest->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => 1500,
        'total_amount' => 3000,
    ]);

    session()->put(CartService::SESSION_KEY, $guest->id);

    Livewire::test(\App\Livewire\Storefront\Account\Auth\Login::class)
        ->set('email', 'merge@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('account.dashboard'));

    $customerCartId = session()->get(CartService::SESSION_KEY);
    $customerCart = Cart::query()->find($customerCartId);

    expect($customerCart)->not->toBeNull();
    expect($customerCart->customer_id)->toBe($customer->id);
    expect($customerCart->lines()->count())->toBe(1);
    expect($customerCart->lines()->first()->variant_id)->toBe($variant->id);
    expect($customerCart->lines()->first()->quantity)->toBe(2);
});
