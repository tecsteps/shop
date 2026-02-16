<?php

use App\Livewire\Storefront\Cart\Show as CartShow;
use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $cartData = createCartWithProduct($this->ctx['store'], priceAmount: 5000);
    // Bind the cart's session_id to the current session
    $cartData['cart']->update(['session_id' => session()->getId()]);
});

it('shows the discount code input on the cart page', function () {
    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->assertSee('Discount code')
        ->assertSee('Apply');
});

it('can apply a valid discount code', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'SAVE10',
        'value_amount' => 10,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', 'SAVE10')
        ->call('applyDiscount')
        ->assertSee('Discount applied')
        ->assertSee('SAVE10');
});

it('shows an error for an invalid discount code', function () {
    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', 'INVALID')
        ->call('applyDiscount')
        ->assertSee('This discount code is not valid.');
});

it('shows an error for an expired discount code', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'EXPIRED',
        'ends_at' => now()->subDay(),
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', 'EXPIRED')
        ->call('applyDiscount')
        ->assertSee('This discount code has expired.');
});

it('can remove an applied discount', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'SAVE10',
        'value_amount' => 10,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', 'SAVE10')
        ->call('applyDiscount')
        ->assertSee('Discount applied')
        ->call('removeDiscount')
        ->assertDontSee('Discount applied')
        ->assertSee('Discount code');
});

it('shows an error for a discount that has reached its usage limit', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'MAXED',
        'usage_limit' => 5,
        'usage_count' => 5,
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', 'MAXED')
        ->call('applyDiscount')
        ->assertSee('This discount code has reached its usage limit.');
});

it('shows an error when discount code is empty', function () {
    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', '')
        ->call('applyDiscount')
        ->assertSee('Please enter a discount code.');
});

it('displays the discount amount in the totals', function () {
    Discount::factory()->for($this->ctx['store'])->create([
        'code' => 'SAVE10',
        'value_amount' => 10, // 10%
    ]);

    Livewire::withHeaders(['Host' => 'shop.test'])
        ->test(CartShow::class)
        ->set('discountCode', 'SAVE10')
        ->call('applyDiscount')
        ->assertSee('Discount')
        ->assertSee('-$5.00');
});
