<?php

use App\Livewire\Admin\Discounts\Edit;
use App\Models\Discount;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function discountEditAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the edit discount page with pre-populated data', function () {
    [$user, $store] = discountEditAdmin();

    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'EDITME',
        'title' => 'Edit Discount',
        'type' => 'code',
        'value_type' => 'percent',
        'value_amount' => 15,
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['discount' => $discount])
        ->assertSet('code', 'EDITME')
        ->assertSet('title', 'Edit Discount')
        ->assertSet('type', 'code')
        ->assertSet('valueType', 'percent')
        ->assertSet('valueAmount', 15)
        ->assertSet('status', 'active')
        ->assertSuccessful();
});

test('it updates discount fields', function () {
    [$user, $store] = discountEditAdmin();

    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'code' => 'ORIGINAL',
        'value_amount' => 10,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['discount' => $discount])
        ->set('code', 'UPDATED')
        ->set('valueAmount', 25)
        ->call('save')
        ->assertDispatched('toast');

    $discount->refresh();

    expect($discount->code)->toBe('UPDATED')
        ->and($discount->value_amount)->toBe(25);
});

test('it validates required fields on update', function () {
    [$user, $store] = discountEditAdmin();

    $discount = Discount::factory()->create([
        'store_id' => $store->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['discount' => $discount])
        ->set('code', '')
        ->call('save')
        ->assertHasErrors(['code']);
});

test('it aborts if discount belongs to a different store', function () {
    [$user, $store] = discountEditAdmin();

    $otherStore = Store::factory()->create();
    $discount = Discount::factory()->create([
        'store_id' => $otherStore->id,
    ]);

    Livewire::actingAs($user)
        ->test(Edit::class, ['discount' => $discount])
        ->assertNotFound();
});
