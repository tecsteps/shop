<?php

use App\Enums\DiscountStatus;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountsIndex;
use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the discounts list page', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.discounts.index'))
        ->assertOk();
});

it('lists discounts for the current store', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SALE20',
    ]);

    Livewire::actingAs($this->user)
        ->test(DiscountsIndex::class)
        ->assertSee('SALE20');
});

it('searches discounts by code', function () {
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SALE20']);
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'WELCOME10']);

    Livewire::actingAs($this->user)
        ->test(DiscountsIndex::class)
        ->set('search', 'SALE')
        ->assertSee('SALE20')
        ->assertDontSee('WELCOME10');
});

it('filters discounts by status', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'ACTIVE1',
        'status' => DiscountStatus::Active,
    ]);
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'DRAFT1',
        'status' => DiscountStatus::Draft,
    ]);

    Livewire::actingAs($this->user)
        ->test(DiscountsIndex::class)
        ->set('statusFilter', 'active')
        ->assertSee('ACTIVE1')
        ->assertDontSee('DRAFT1');
});

it('renders the discount create form', function () {
    actingAsAdmin($this->user, $this->store)
        ->get(route('admin.discounts.create'))
        ->assertOk();
});

it('creates a new discount via form', function () {
    Livewire::actingAs($this->user)
        ->test(DiscountForm::class)
        ->set('code', 'NEWDISCOUNT')
        ->set('type', 'code')
        ->set('valueType', 'percent')
        ->set('value', 1500)
        ->set('status', 'active')
        ->call('save')
        ->assertRedirect();

    expect(Discount::query()->withoutGlobalScopes()->where('code', 'NEWDISCOUNT')->exists())
        ->toBeTrue();
});
