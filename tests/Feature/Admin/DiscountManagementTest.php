<?php

use App\Enums\DiscountStatus;
use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use Livewire\Livewire;

it('renders the discounts list page', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Discount::factory()->count(3)->create(['store_id' => $ctx['store']->id]);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Discounts');
});

it('filters discounts by status', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Discount::factory()->create(['store_id' => $ctx['store']->id, 'code' => 'ACTIVE10', 'status' => DiscountStatus::Active]);
    Discount::factory()->expired()->create(['store_id' => $ctx['store']->id, 'code' => 'EXPIRED20']);

    Livewire::test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('ACTIVE10')
        ->assertDontSee('EXPIRED20');
});

it('creates a new discount code', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', 'TESTCODE')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('discounts', [
        'store_id' => $ctx['store']->id,
        'code' => 'TESTCODE',
    ]);
});

it('edits an existing discount', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'ORIGINAL',
    ]);

    Livewire::test(Form::class, ['discount' => $discount])
        ->assertSet('code', 'ORIGINAL')
        ->set('code', 'UPDATED')
        ->call('save')
        ->assertDispatched('toast');

    expect($discount->fresh()->code)->toBe('UPDATED');
});

it('generates a random discount code', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Form::class)
        ->assertSet('code', '')
        ->call('generateCode')
        ->assertNotSet('code', '');
});

it('creates an automatic discount', function (): void {
    $ctx = createStoreContext();
    actingAsAdmin($ctx['user'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('type', 'automatic')
        ->set('valueType', 'fixed')
        ->set('valueAmount', '5')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('discounts', [
        'store_id' => $ctx['store']->id,
        'type' => 'automatic',
        'code' => null,
    ]);
});
