<?php

use App\Enums\DiscountStatus;
use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Discounts\Index as DiscountIndex;
use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('requires authentication to access discounts page', function () {
    auth()->logout();
    $this->get('/admin/discounts')->assertRedirect('/admin/login');
});

it('renders the discounts index page', function () {
    $this->get('/admin/discounts')
        ->assertStatus(200)
        ->assertSee('Discounts');
});

it('lists discounts with search', function () {
    Discount::factory()->create(['store_id' => $this->ctx['store']->id, 'code' => 'SUMMER20']);
    Discount::factory()->create(['store_id' => $this->ctx['store']->id, 'code' => 'WINTER10']);

    $component = Livewire::test(DiscountIndex::class);
    $component->assertSee('SUMMER20');
    $component->assertSee('WINTER10');

    $component->set('search', 'SUMMER');
    $component->assertSee('SUMMER20');
    $component->assertDontSee('WINTER10');
});

it('filters discounts by status', function () {
    Discount::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'code' => 'ACTIVE1',
        'status' => DiscountStatus::Active,
    ]);
    Discount::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'code' => 'DRAFT1',
        'status' => DiscountStatus::Draft,
    ]);

    $component = Livewire::test(DiscountIndex::class);
    $component->set('statusFilter', 'active');
    $component->assertSee('ACTIVE1');
    $component->assertDontSee('DRAFT1');
});

it('filters discounts by type', function () {
    Discount::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'code' => 'CODE1',
        'type' => 'code',
    ]);
    Discount::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'code' => null,
        'type' => 'automatic',
    ]);

    $component = Livewire::test(DiscountIndex::class);
    $component->set('typeFilter', 'code');

    expect($component->instance()->discounts->total())->toBe(1);
});

it('renders the discount create form', function () {
    $this->get('/admin/discounts/create')
        ->assertStatus(200)
        ->assertSee('Add discount');
});

it('creates a discount code', function () {
    $component = Livewire::test(DiscountForm::class);

    $component->set('type', 'code');
    $component->set('code', 'TESTCODE');
    $component->set('valueType', 'percent');
    $component->set('valueAmount', 20);
    $component->set('status', 'active');
    $component->call('save');

    $discount = Discount::where('code', 'TESTCODE')->first();
    expect($discount)->not->toBeNull();
    expect($discount->value_amount)->toBe(20);
    expect($discount->status)->toBe(DiscountStatus::Active);
});

it('creates a fixed amount discount', function () {
    $component = Livewire::test(DiscountForm::class);

    $component->set('type', 'code');
    $component->set('code', 'FIXED50');
    $component->set('valueType', 'fixed');
    $component->set('valueAmount', 5000);
    $component->set('status', 'active');
    $component->call('save');

    $discount = Discount::where('code', 'FIXED50')->first();
    expect($discount)->not->toBeNull();
    expect($discount->value_type->value)->toBe('fixed');
    expect($discount->value_amount)->toBe(5000);
});

it('updates an existing discount', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'code' => 'OLDCODE',
        'value_amount' => 10,
    ]);

    $component = Livewire::test(DiscountForm::class, ['discount' => $discount]);
    $component->set('code', 'NEWCODE');
    $component->set('valueAmount', 25);
    $component->call('save');

    $discount->refresh();
    expect($discount->code)->toBe('NEWCODE');
    expect($discount->value_amount)->toBe(25);
});

it('validates required code for code type discounts', function () {
    $component = Livewire::test(DiscountForm::class);

    $component->set('type', 'code');
    $component->set('code', '');
    $component->set('valueAmount', 10);
    $component->call('save');

    $component->assertHasErrors('code');
});

it('validates discount end date is after start date', function () {
    $component = Livewire::test(DiscountForm::class);

    $component->set('type', 'code');
    $component->set('code', 'DATETEST');
    $component->set('valueAmount', 10);
    $component->set('startsAt', '2026-06-01T00:00');
    $component->set('endsAt', '2026-05-01T00:00');
    $component->call('save');

    $component->assertHasErrors('endsAt');
});

it('shows empty state when no discounts exist', function () {
    $component = Livewire::test(DiscountIndex::class);
    $component->assertSee('No discounts yet');
});

it('renders the discount edit form with data', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->ctx['store']->id,
        'code' => 'EDITME',
    ]);

    $this->get("/admin/discounts/{$discount->id}/edit")
        ->assertStatus(200)
        ->assertSee('Edit discount');
});
