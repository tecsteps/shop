<?php

use App\Livewire\Storefront\Cart\Show;
use App\Livewire\Storefront\CartDrawer;
use App\Models\CartLine;
use Livewire\Livewire;
use Tests\Support\CommerceTestHelper;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the cart page with lines and totals', function () {
    ['store' => $store, 'domain' => $domain] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);

    $response = $this->withSession(['cart_id' => $cart->id])->get(storefrontUrl($domain->hostname, '/cart'));

    $response->assertOk();
    $response->assertSee('Your Cart');
    $response->assertSee($cart->cartLines()->first()->variant->product->title);
});

it('renders an empty cart state', function () {
    ['domain' => $domain] = $this->createStoreContext();

    $response = $this->get(storefrontUrl($domain->hostname, '/cart'));

    $response->assertOk();
    $response->assertSee('Your cart is empty');
});

it('updates quantities and removes lines', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store, [['price' => 2500, 'qty' => 1]]);
    session()->put('cart_id', $cart->id);
    $lineId = $cart->cartLines()->first()->id;

    Livewire::test(Show::class)
        ->call('incrementLine', $lineId)
        ->assertDispatched('cart-updated');

    expect(CartLine::query()->find($lineId)->quantity)->toBe(2);

    Livewire::test(Show::class)
        ->call('decrementLine', $lineId);

    expect(CartLine::query()->find($lineId)->quantity)->toBe(1);

    Livewire::test(Show::class)
        ->call('removeLine', $lineId)
        ->assertSet('cartNotice', 'Item removed');

    expect(CartLine::query()->find($lineId))->toBeNull();
});

it('shows a conflict message on cart version mismatch', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    session()->put('cart_id', $cart->id);
    $lineId = $cart->cartLines()->first()->id;

    Livewire::test(Show::class)
        ->set('cartVersion', 999)
        ->call('incrementLine', $lineId)
        ->assertSet('cartNotice', 'Your cart was updated elsewhere. Please review and try again.');
});

it('applies and removes discount codes on the cart page', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    session()->put('cart_id', $cart->id);
    App\Models\Discount::factory()->create([
        'store_id' => $store->id,
        'type' => App\Enums\DiscountType::Code,
        'code' => 'SAVE10',
        'value_type' => App\Enums\DiscountValueType::Percent,
        'value_amount' => 10,
        'status' => App\Enums\DiscountStatus::Active,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit' => null,
    ]);

    Livewire::test(Show::class)
        ->set('discountCodeInput', 'SAVE10')
        ->call('applyDiscount')
        ->assertSet('cartNotice', 'Discount applied');

    expect(session()->get('storefront.discount_code'))->toBe('SAVE10');

    Livewire::test(Show::class)
        ->call('removeDiscount');

    expect(session()->has('storefront.discount_code'))->toBeFalse();
});

it('rejects invalid discount codes', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    session()->put('cart_id', $cart->id);

    Livewire::test(Show::class)
        ->set('discountCodeInput', 'NOPE')
        ->call('applyDiscount')
        ->assertHasErrors('discountCodeInput');
});

it('starts checkout from the cart page', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    session()->put('cart_id', $cart->id);

    Livewire::test(Show::class)
        ->call('startCheckout')
        ->assertRedirect();

    expect(App\Models\Checkout::query()->where('cart_id', $cart->id)->exists())->toBeTrue();
});

it('opens the cart drawer on cart updates', function () {
    ['store' => $store] = $this->createStoreContext();
    $cart = CommerceTestHelper::cartWithLines($store);
    session()->put('cart_id', $cart->id);

    Livewire::test(CartDrawer::class)
        ->assertSet('open', false)
        ->dispatch('open-cart-drawer')
        ->assertSet('open', true)
        ->assertSee($cart->cartLines()->first()->variant->product->title)
        ->call('closeDrawer')
        ->assertSet('open', false);
});
