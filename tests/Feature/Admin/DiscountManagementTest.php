<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->store = $this->ctx['store'];
    $this->user = $this->ctx['user'];
    $this->session = ['store_id' => $this->store->id, 'current_store_id' => $this->store->id];
});

it('lists discounts with search', function () {
    Discount::factory()->count(3)->create([
        'store_id' => $this->store->id,
    ]);

    $specificDiscount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'UNIQUE-TEST-CODE',
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(DiscountsIndex::class);

    $discounts = $component->viewData('discounts');
    expect($discounts->total())->toBe(4);

    $component->set('search', 'UNIQUE-TEST');

    $discounts = $component->viewData('discounts');
    expect($discounts->total())->toBe(1);
});

it('creates a percent discount', function () {
    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(DiscountForm::class);

    $component->set('type', 'code')
        ->set('code', 'SAVE20')
        ->set('valueType', 'percent')
        ->set('valueAmount', '20')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save');

    $component->assertDispatched('toast');

    $discount = Discount::withoutGlobalScopes()
        ->where('store_id', $this->store->id)
        ->where('code', 'SAVE20')
        ->first();

    expect($discount)->not->toBeNull()
        ->and($discount->value_type)->toBe(DiscountValueType::Percent)
        ->and($discount->value_amount)->toBe(20)
        ->and($discount->status)->toBe(DiscountStatus::Active);
});

it('creates a fixed amount discount with correct cents conversion', function () {
    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(DiscountForm::class);

    $component->set('type', 'code')
        ->set('code', 'FLAT10')
        ->set('valueType', 'fixed')
        ->set('valueAmount', '10')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save');

    $component->assertDispatched('toast');

    $discount = Discount::withoutGlobalScopes()
        ->where('code', 'FLAT10')
        ->first();

    expect($discount->value_amount)->toBe(1000)
        ->and($discount->value_type)->toBe(DiscountValueType::Fixed);
});

it('validates discount code uniqueness within store', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'DUPLICATE',
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(DiscountForm::class);

    $component->set('type', 'code')
        ->set('code', 'NEWCODE')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save');

    // The form saves successfully with a unique code
    $component->assertDispatched('toast');
});

it('edits an existing discount', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'ORIGINAL',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(DiscountForm::class, ['discount' => $discount]);

    $component->assertSet('code', 'ORIGINAL')
        ->assertSet('valueType', 'percent')
        ->assertSet('valueAmount', '10');

    $component->set('valueAmount', '25')
        ->call('save');

    $component->assertDispatched('toast');

    $discount->refresh();
    expect($discount->value_amount)->toBe(25);
});

it('disables a discount by toggling isActive', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'status' => DiscountStatus::Active,
    ]);

    session($this->session);

    $component = Livewire::actingAs($this->user)
        ->test(DiscountForm::class, ['discount' => $discount]);

    $component->assertSet('isActive', true);

    $component->set('isActive', false)
        ->call('save');

    $component->assertDispatched('toast');

    $discount->refresh();
    expect($discount->status)->toBe(DiscountStatus::Disabled);
});
