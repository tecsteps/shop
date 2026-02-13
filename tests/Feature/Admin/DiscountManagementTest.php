<?php

use App\Enums\DiscountStatus;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountIndex;
use App\Models\Discount;
use Livewire\Livewire;

it('lists discounts', function () {
    $ctx = createStoreContext();
    Discount::factory()->count(3)->create(['store_id' => $ctx['store']->id]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(DiscountIndex::class)
        ->assertOk()
        ->assertSee('Discounts');
});

it('creates a percent discount', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(DiscountForm::class)
        ->set('code', 'SAVE10')
        ->set('type', 'code')
        ->set('valueType', 'percent')
        ->set('valueAmount', 1000)
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    $discount = Discount::query()->where('code', 'SAVE10')->first();
    expect($discount)->not->toBeNull()
        ->and($discount->value_type->value)->toBe('percent')
        ->and($discount->value_amount)->toBe(1000);
});

it('creates a fixed discount', function () {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(DiscountForm::class)
        ->set('code', 'FLAT5')
        ->set('type', 'code')
        ->set('valueType', 'fixed')
        ->set('valueAmount', 500)
        ->set('status', 'active')
        ->call('save')
        ->assertHasNoErrors();

    $discount = Discount::query()->where('code', 'FLAT5')->first();
    expect($discount)->not->toBeNull()
        ->and($discount->value_type->value)->toBe('fixed');
});

it('validates discount code uniqueness within store', function () {
    $ctx = createStoreContext();

    Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'UNIQUE10',
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(DiscountForm::class)
        ->set('code', 'UNIQUE10')
        ->set('type', 'code')
        ->set('valueType', 'percent')
        ->set('valueAmount', 1000)
        ->set('status', 'active')
        ->call('save')
        ->assertHasErrors(['code']);
});

it('edits a discount', function () {
    $ctx = createStoreContext();
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'ORIGINAL',
        'value_amount' => 1000,
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(DiscountForm::class, ['discount' => $discount])
        ->assertSet('mode', 'edit')
        ->set('code', 'UPDATED')
        ->set('valueAmount', 2000)
        ->call('save')
        ->assertHasNoErrors();

    $discount->refresh();
    expect($discount->code)->toBe('UPDATED')
        ->and($discount->value_amount)->toBe(2000);
});

it('disables a discount', function () {
    $ctx = createStoreContext();
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'status' => DiscountStatus::Active,
    ]);

    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(DiscountForm::class, ['discount' => $discount])
        ->set('status', 'disabled')
        ->call('save')
        ->assertHasNoErrors();

    $discount->refresh();
    expect($discount->status)->toBe(DiscountStatus::Disabled);
});
