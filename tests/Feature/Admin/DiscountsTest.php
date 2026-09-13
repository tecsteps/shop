<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists discounts scoped to the current store', function () {
    Discount::factory()->create(['store_id' => $this->store->getKey(), 'code' => 'WELCOME10']);

    $this->get(route('admin.discounts.index'))
        ->assertOk()
        ->assertSee('WELCOME10');
});

it('creates a percent discount with a minimum purchase rule', function () {
    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', 'SAVE20')
        ->set('valueType', 'percent')
        ->set('valueAmount', '20')
        ->set('minimumPurchaseAmount', '50.00')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    $discount = Discount::query()->where('store_id', $this->store->getKey())->where('code', 'SAVE20')->firstOrFail();

    expect($discount->value_type)->toBe(DiscountValueType::Percent)
        ->and($discount->value_amount)->toBe(20)
        ->and($discount->rules_json['min_purchase_amount'])->toBe(5000);
});

it('creates a fixed-amount discount converting euros to cents', function () {
    Livewire::test(Form::class)
        ->set('type', 'code')
        ->set('code', 'FLAT5')
        ->set('valueType', 'fixed')
        ->set('valueAmount', '5.00')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    expect(Discount::query()->where('code', 'FLAT5')->firstOrFail()->value_amount)->toBe(500);
});

it('edits a discount', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->getKey(),
        'code' => 'OLD',
        'status' => DiscountStatus::Active,
    ]);

    Livewire::test(Form::class, ['discount' => $discount])
        ->set('code', 'NEWCODE')
        ->call('save')
        ->assertDispatched('toast');

    expect($discount->refresh()->code)->toBe('NEWCODE');
});

it('deletes a discount with confirmation', function () {
    $discount = Discount::factory()->create(['store_id' => $this->store->getKey()]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $discount->getKey())
        ->call('deleteDiscount')
        ->assertDispatched('toast');

    expect(Discount::query()->whereKey($discount->getKey())->exists())->toBeFalse();
});

it('returns 404 when editing a discount from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $discount = Discount::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.discounts.edit', $discount))->assertNotFound();
});
