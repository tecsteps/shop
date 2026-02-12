<?php

use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function discountAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the discounts index page', function () {
    [$user, $store] = discountAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Discounts')
        ->assertSuccessful();
});

test('it lists discounts for the current store', function () {
    [$user, $store] = discountAdmin();

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SUMMER20',
    ]);

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'WINTER10',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('SUMMER20')
        ->assertSee('WINTER10');
});

test('it does not show discounts from other stores', function () {
    [$user, $store] = discountAdmin();

    $otherStore = Store::factory()->create();

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'MYCODE',
    ]);

    Discount::factory()->create([
        'store_id' => $otherStore->id,
        'code' => 'OTHERCODE',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('MYCODE')
        ->assertDontSee('OTHERCODE');
});

test('it filters discounts by search term', function () {
    [$user, $store] = discountAdmin();

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'SUMMER20',
    ]);

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'WINTER10',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'SUMMER')
        ->assertSee('SUMMER20')
        ->assertDontSee('WINTER10');
});

test('it filters discounts by status', function () {
    [$user, $store] = discountAdmin();

    Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'ACTIVE10',
        'status' => 'active',
    ]);

    Discount::factory()->draft()->create([
        'store_id' => $store->id,
        'code' => 'DRAFT10',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('ACTIVE10')
        ->assertDontSee('DRAFT10');
});

test('it deletes a discount', function () {
    [$user, $store] = discountAdmin();

    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'DELETEME',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('delete', $discount->id)
        ->assertDispatched('toast');

    expect(Discount::find($discount->id))->toBeNull();
});

test('it shows empty state when no discounts exist', function () {
    [$user, $store] = discountAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No discounts');
});
