<?php

use App\Livewire\Storefront\CartCount;
use App\Livewire\Storefront\FeaturedProducts;
use App\Livewire\Storefront\NewsletterSignup;
use App\Livewire\Storefront\QuickAdd;
use App\Models\CartLine;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('quick adds a variant to the session cart', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    Livewire::test(QuickAdd::class, ['variantId' => $variant->id])
        ->call('add')
        ->assertDispatched('cart-updated');

    expect(CartLine::query()->where('variant_id', $variant->id)->exists())->toBeTrue();
});

it('quick add reports out of stock variants', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store, [], ['quantity_on_hand' => 0]);

    Livewire::test(QuickAdd::class, ['variantId' => $variant->id])
        ->call('add')
        ->assertSet('message', 'This product is currently out of stock');
});

it('subscribes newsletter emails with validation', function () {
    Livewire::test(NewsletterSignup::class)
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors('email');

    Livewire::test(NewsletterSignup::class)
        ->set('email', 'fan@example.com')
        ->call('subscribe')
        ->assertSet('subscribed', true)
        ->assertSee('Thanks for subscribing!');
});

it('renders featured products scoped to the store', function () {
    ['store' => $store] = $this->createStoreContext();
    $variant = CommerceTestHelper::purchasableVariant($store);

    Livewire::test(FeaturedProducts::class, ['count' => 4])
        ->assertSee($variant->product->title)
        ->assertStatus(200);
});

it('counts session cart items for the badge', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 3]]);
    session()->put('cart_id', $cart->id);

    Livewire::test(CartCount::class)
        ->assertSet('count', 3)
        ->assertSee('3');
});

it('isolates session carts by store', function () {
    ['store' => $store] = $this->createStoreContext();
    ['domain' => $domainB] = $this->createStoreContext();
    app(App\Tenancy\CurrentStore::class)->set($store);
    $cart = CommerceTestHelper::cartWithLines($store);
    $title = $cart->cartLines()->first()->variant->product->title;

    $response = $this->withSession(['cart_id' => $cart->id])->get(storefrontUrl($domainB->hostname, '/cart'));

    $response->assertOk();
    $response->assertSee('Your cart is empty');
    $response->assertDontSee($title);
});
