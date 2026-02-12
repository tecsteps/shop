<?php

use App\Livewire\Admin\Discounts\Create;
use App\Models\Discount;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function discountCreateAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the create discount page', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->assertSee('Create discount')
        ->assertSuccessful();
});

test('it creates a percentage discount', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('code', 'SAVE20')
        ->set('title', 'Save 20 percent')
        ->set('type', 'code')
        ->set('valueType', 'percent')
        ->set('valueAmount', 20)
        ->set('status', 'active')
        ->call('save')
        ->assertDispatched('toast')
        ->assertRedirect();

    $discount = Discount::where('code', 'SAVE20')->first();

    expect($discount)->not->toBeNull()
        ->and($discount->store_id)->toBe($store->id)
        ->and($discount->value_type->value)->toBe('percent')
        ->and($discount->value_amount)->toBe(20)
        ->and($discount->status->value)->toBe('active');
});

test('it creates a fixed amount discount', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('code', 'FLAT500')
        ->set('type', 'code')
        ->set('valueType', 'fixed')
        ->set('valueAmount', 500)
        ->set('status', 'draft')
        ->call('save')
        ->assertRedirect();

    $discount = Discount::where('code', 'FLAT500')->first();

    expect($discount->value_type->value)->toBe('fixed')
        ->and($discount->value_amount)->toBe(500);
});

test('it creates a discount with schedule', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('code', 'TIMED')
        ->set('type', 'code')
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->set('status', 'active')
        ->set('startsAt', '2026-01-01T00:00')
        ->set('endsAt', '2026-12-31T23:59')
        ->call('save')
        ->assertRedirect();

    $discount = Discount::where('code', 'TIMED')->first();

    expect($discount->starts_at)->not->toBeNull()
        ->and($discount->ends_at)->not->toBeNull();
});

test('it validates required fields', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('code', '')
        ->call('save')
        ->assertHasErrors(['code']);
});

test('it validates value type values', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('code', 'TEST')
        ->set('valueType', 'invalid')
        ->call('save')
        ->assertHasErrors(['valueType']);
});

test('it uppercases the discount code', function () {
    [$user, $store] = discountCreateAdmin();

    Livewire::actingAs($user)
        ->test(Create::class)
        ->set('code', 'lowercase')
        ->set('type', 'code')
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->set('status', 'draft')
        ->call('save')
        ->assertRedirect();

    expect(Discount::where('code', 'LOWERCASE')->exists())->toBeTrue();
});
