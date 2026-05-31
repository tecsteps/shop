<?php

use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

it('lists discounts', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Discount::factory()->count(3)->create(['store_id' => $this->store->id]);

    $component = Livewire::test(Index::class);

    expect($component->instance()->discounts->total())->toBe(3);
});

it('creates a percent discount', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', 'SAVE10')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('discounts', [
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);
});

it('creates a fixed discount', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', '5OFF')
        ->set('valueType', 'fixed')
        ->set('valueAmount', '5.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('discounts', [
        'store_id' => $this->store->id,
        'code' => '5OFF',
        'value_type' => 'fixed',
        'value_amount' => 500,
    ]);
});

it('validates discount code uniqueness within store', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10', 'type' => 'code']);

    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', 'SAVE10')
        ->set('valueType', 'percent')
        ->set('valueAmount', '15')
        ->call('save')
        ->assertHasErrors('code');
});

it('edits a discount', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'value_type' => 'percent',
        'value_amount' => 10,
        'type' => 'code',
        'code' => 'EDIT10',
    ]);

    Livewire::test(Form::class, ['discount' => $discount])
        ->set('valueAmount', '15')
        ->call('save')
        ->assertHasNoErrors();

    expect($discount->fresh()->value_amount)->toBe(15);
});

it('disables a discount', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $discount = Discount::factory()->create(['store_id' => $this->store->id, 'status' => 'active', 'type' => 'code', 'code' => 'OFF']);

    Livewire::test(Form::class, ['discount' => $discount])
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($discount->fresh()->status->value)->toBe('disabled');
});
