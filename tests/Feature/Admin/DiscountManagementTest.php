<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists discounts', function () {
    $discounts = Discount::factory()->count(3)->for($this->store)->create();

    actingAsAdmin($this->user)
        ->get('/admin/discounts')
        ->assertOk();

    $component = Livewire::test(DiscountsIndex::class);

    expect($component->instance()->discounts()->total())->toBe(3);

    foreach ($discounts as $discount) {
        $component->assertSee($discount->code);
    }
});

it('creates a percent discount', function () {
    actingAsAdmin($this->user);

    Livewire::test(DiscountForm::class)
        ->set('type', 'code')
        ->set('code', 'SAVE10')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('discounts', [
        'store_id' => $this->store->getKey(),
        'code' => 'SAVE10',
        'value_type' => 'percent',
        'value_amount' => 10,
    ]);
});

it('creates a fixed discount', function () {
    actingAsAdmin($this->user);

    Livewire::test(DiscountForm::class)
        ->set('type', 'code')
        ->set('code', '5OFF')
        ->set('valueType', 'fixed')
        ->set('valueAmount', '5.00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('discounts', [
        'store_id' => $this->store->getKey(),
        'code' => '5OFF',
        'value_type' => 'fixed',
        'value_amount' => 500,
    ]);
});

it('validates discount code uniqueness within store', function () {
    Discount::factory()->for($this->store)->create(['code' => 'SAVE10']);

    actingAsAdmin($this->user);

    Livewire::test(DiscountForm::class)
        ->set('type', 'code')
        ->set('code', 'SAVE10')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->call('save')
        ->assertHasErrors(['code']);

    expect(Discount::query()->where('code', 'SAVE10')->count())->toBe(1);
});

it('edits a discount', function () {
    $discount = Discount::factory()->for($this->store)->create([
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 10,
    ]);

    actingAsAdmin($this->user);

    Livewire::test(DiscountForm::class, ['discountId' => $discount->getKey()])
        ->set('valueAmount', '15')
        ->call('save')
        ->assertHasNoErrors();

    expect($discount->refresh()->value_amount)->toBe(15);
});

it('disables a discount', function () {
    $discount = Discount::factory()->for($this->store)->create([
        'status' => DiscountStatus::Active,
    ]);

    actingAsAdmin($this->user);

    Livewire::test(DiscountForm::class, ['discountId' => $discount->getKey()])
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($discount->refresh()->status)->toBe(DiscountStatus::Disabled);
});
