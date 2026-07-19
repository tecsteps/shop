<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use App\Models\Product;
use Livewire\Livewire;

beforeEach(function () {
    $this->store = $this->createStore();
    $this->user = $this->createUserWithRole($this->store, 'owner');
    $this->bindStore($this->store);
});

test('lists discounts', function () {
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10']);
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE20']);
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE30']);

    $this->actingAs($this->user)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/discounts')
        ->assertOk()
        ->assertSee('SAVE10')
        ->assertSee('SAVE20')
        ->assertSee('SAVE30');
});

test('filters discounts by status and type', function () {
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'ACTIVEONE']);
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'DISABLEDONE',
        'status' => DiscountStatus::Disabled,
    ]);
    Discount::factory()->automatic()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('statusFilter', 'disabled')
        ->assertSee('DISABLEDONE')
        ->assertDontSee('ACTIVEONE')
        ->set('statusFilter', 'all')
        ->set('typeFilter', 'automatic')
        ->assertSee('Automatic')
        ->assertDontSee('ACTIVEONE')
        ->assertDontSee('DISABLEDONE');
});

test('searches discounts by code', function () {
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SUMMER25']);
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'WINTER25']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->set('search', 'SUMMER')
        ->assertSee('SUMMER25')
        ->assertDontSee('WINTER25');
});

test('creates a percent discount with rules', function () {
    $product = Product::factory()->create(['store_id' => $this->store->id, 'title' => 'Blue Shirt']);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('code', 'save10') // stored uppercase
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->set('minimumPurchaseAmount', 5000)
        ->set('usageLimit', 100)
        ->call('addProduct', $product->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $discount = Discount::query()->where('code', 'SAVE10')->sole();

    expect($discount->type)->toBe(DiscountType::Code)
        ->and($discount->value_type)->toBe(DiscountValueType::Percent)
        ->and($discount->value_amount)->toBe(10)
        ->and($discount->status)->toBe(DiscountStatus::Active)
        ->and($discount->usage_limit)->toBe(100)
        ->and($discount->rules_json['min_purchase_amount'])->toBe(5000)
        ->and($discount->rules_json['applicable_product_ids'])->toBe([$product->id]);
});

test('creates a fixed discount', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('code', '5OFF')
        ->set('valueType', 'fixed')
        ->set('valueAmount', 500)
        ->call('save')
        ->assertHasNoErrors();

    $discount = Discount::query()->where('code', '5OFF')->sole();

    expect($discount->value_type)->toBe(DiscountValueType::Fixed)
        ->and($discount->value_amount)->toBe(500);
});

test('creates an automatic free shipping discount without a code', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('type', 'automatic')
        ->set('valueType', 'free_shipping')
        ->call('save')
        ->assertHasNoErrors();

    $discount = Discount::query()->sole();

    expect($discount->type)->toBe(DiscountType::Automatic)
        ->and($discount->code)->toBeNull()
        ->and($discount->value_type)->toBe(DiscountValueType::FreeShipping)
        ->and($discount->value_amount)->toBe(0);
});

test('requires a code for code discounts', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->call('save')
        ->assertHasErrors('code');
});

test('validates percent value cannot exceed 100', function () {
    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('code', 'TOOMUCH')
        ->set('valueType', 'percent')
        ->set('valueAmount', 101)
        ->call('save')
        ->assertHasErrors('valueAmount');

    expect(Discount::query()->where('code', 'TOOMUCH')->exists())->toBeFalse();
});

test('validates discount code uniqueness within store case-insensitively', function () {
    Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10']);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('code', 'save10')
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->call('save')
        ->assertHasErrors('code');

    expect(Discount::query()->where('store_id', $this->store->id)->count())->toBe(1);
});

test('allows the same code in a different store', function () {
    Discount::factory()->create(['code' => 'SAVE10']); // other store

    Livewire::actingAs($this->user);
    Livewire::test(Form::class)
        ->set('code', 'SAVE10')
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->call('save')
        ->assertHasNoErrors();

    expect(Discount::query()->where('store_id', $this->store->id)->where('code', 'SAVE10')->exists())->toBeTrue();
});

test('edits a discount', function () {
    $discount = Discount::factory()->percent(10)->create([
        'store_id' => $this->store->id,
        'code' => 'SAVE10',
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['discount' => $discount])
        ->assertSet('code', 'SAVE10')
        ->assertSet('valueAmount', 10)
        ->set('valueAmount', 15)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Discount saved');

    expect($discount->refresh()->value_amount)->toBe(15);
});

test('editing a discount keeps its own code valid', function () {
    $discount = Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10']);

    Livewire::actingAs($this->user);
    Livewire::test(Form::class, ['discount' => $discount])
        ->set('code', 'save10') // same code, different case
        ->call('save')
        ->assertHasNoErrors();
});

test('disables and re-enables a discount', function () {
    $discount = Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('disable', $discount->id)
        ->assertDispatched('toast', type: 'success', message: 'Discount disabled');

    expect($discount->refresh()->status)->toBe(DiscountStatus::Disabled);

    Livewire::test(Index::class)
        ->call('enable', $discount->id)
        ->assertDispatched('toast', type: 'success', message: 'Discount activated');

    expect($discount->refresh()->status)->toBe(DiscountStatus::Active);
});

test('activates a draft discount', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'status' => DiscountStatus::Draft,
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('enable', $discount->id);

    expect($discount->refresh()->status)->toBe(DiscountStatus::Active);
});

test('does not disable a discount that is not active', function () {
    $discount = Discount::factory()->create([
        'store_id' => $this->store->id,
        'status' => DiscountStatus::Disabled,
    ]);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('disable', $discount->id)
        ->assertDispatched('toast', type: 'error');

    expect($discount->refresh()->status)->toBe(DiscountStatus::Disabled);
});

test('deletes a discount as owner', function () {
    $discount = Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10']);

    Livewire::actingAs($this->user);
    Livewire::test(Index::class)
        ->call('confirmDelete', $discount->id)
        ->assertSet('confirmingDelete', true)
        ->call('delete')
        ->assertDispatched('toast', type: 'success', message: 'Discount deleted');

    expect(Discount::query()->whereKey($discount->id)->exists())->toBeFalse();
});

test('staff cannot delete a discount', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');
    $discount = Discount::factory()->create(['store_id' => $this->store->id, 'code' => 'SAVE10']);

    Livewire::actingAs($staff);
    Livewire::test(Index::class)
        ->set('deletingId', $discount->id)
        ->call('delete')
        ->assertForbidden();

    expect(Discount::query()->whereKey($discount->id)->exists())->toBeTrue();
});

test('staff can update but support cannot create discounts', function () {
    $staff = $this->createUserWithRole($this->store, 'staff');
    $support = $this->createUserWithRole($this->store, 'support');
    $discount = Discount::factory()->create(['store_id' => $this->store->id]);

    Livewire::actingAs($staff);
    Livewire::test(Index::class)
        ->call('disable', $discount->id)
        ->assertDispatched('toast', type: 'success');

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/discounts/create')
        ->assertForbidden();

    $this->actingAs($support)
        ->withSession(['current_store_id' => $this->store->id])
        ->get('/admin/discounts')
        ->assertOk();
});

test('guests are redirected from admin discount pages', function () {
    $this->get('/admin/discounts')->assertRedirect('/admin/login');
    $this->get('/admin/discounts/create')->assertRedirect('/admin/login');
});
