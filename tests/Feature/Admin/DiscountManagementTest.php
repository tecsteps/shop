<?php

use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use Livewire\Livewire;

it('creates a discount', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dm-create.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', 'SAVE10')
        ->set('value_type', 'percent')
        ->set('value_amount', 10)
        ->set('starts_at', now()->format('Y-m-d\TH:i'))
        ->set('status', 'active')
        ->call('save');

    $discount = Discount::query()->where('code', 'SAVE10')->first();
    expect($discount)->not->toBeNull();
    expect($discount->value_amount)->toBe(10);
});

it('validates the discount form', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dm-valid.test']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Form::class)
        ->set('type', 'invalid')
        ->set('value_type', 'bogus')
        ->set('value_amount', -1)
        ->set('starts_at', '')
        ->set('status', 'foo')
        ->call('save')
        ->assertHasErrors(['type', 'value_type', 'value_amount', 'starts_at', 'status']);
});

it('lists discounts and deletes', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dm-list.test']);
    $discount = Discount::factory()->create(['store_id' => $ctx['store']->id, 'code' => 'KEEPME']);
    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Index::class)
        ->assertSee('KEEPME')
        ->call('delete', $discount->id);

    expect(Discount::query()->find($discount->id))->toBeNull();
});

it('updates an existing discount', function (): void {
    $ctx = $this->createStoreContext(['hostname' => 'dm-edit.test']);
    $discount = Discount::factory()->create([
        'store_id' => $ctx['store']->id,
        'code' => 'OLD',
        'value_type' => 'percent',
        'value_amount' => 5,
    ]);

    $this->actingAsAdmin($ctx['owner'], $ctx['store']);

    Livewire::test(Form::class, ['discount' => $discount])
        ->set('code', 'NEW')
        ->set('value_amount', 25)
        ->call('save');

    $fresh = $discount->fresh();
    expect($fresh->code)->toBe('NEW');
    expect($fresh->value_amount)->toBe(25);
});
