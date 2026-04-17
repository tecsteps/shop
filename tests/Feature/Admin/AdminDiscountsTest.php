<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Discount;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('discounts index requires authentication', function () {
    $this->get(route('admin.discounts.index'))
        ->assertRedirect(route('admin.login'));
});

test('discounts index displays discounts list', function () {
    $user = User::factory()->create();
    Discount::factory()->create(['code' => 'SUMMER20']);

    $this->actingAs($user)
        ->get(route('admin.discounts.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('discounts index can search by code', function () {
    $user = User::factory()->create();
    Discount::factory()->create(['code' => 'SUMMER20']);
    Discount::factory()->create(['code' => 'WINTER10']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('search', 'SUMMER')
        ->assertSee('SUMMER20')
        ->assertDontSee('WINTER10');
});

test('discounts index can filter by status', function () {
    $user = User::factory()->create();
    Discount::factory()->create(['code' => 'ACTIVE1', 'status' => DiscountStatus::Active]);
    Discount::factory()->expired()->create(['code' => 'EXPIRED1']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->set('statusFilter', 'active')
        ->assertSee('ACTIVE1')
        ->assertDontSee('EXPIRED1');
});

test('discount create form renders', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.discounts.create'))
        ->assertOk()
        ->assertSeeLivewire(Form::class);
});

test('discount can be created', function () {
    $ctx = createStoreContext();

    Livewire::actingAs($ctx['user'])
        ->test(Form::class)
        ->set('type', 'code')
        ->set('code', 'NEWCODE')
        ->set('valueType', 'percent')
        ->set('valueAmount', '15')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    expect(Discount::where('code', 'NEWCODE')->exists())->toBeTrue();
});

test('discount code can be generated', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Form::class)
        ->call('generateCode');

    expect($component->get('code'))->not->toBeEmpty()
        ->and(strlen($component->get('code')))->toBe(8);
});

test('discount edit form loads existing data', function () {
    $user = User::factory()->create();
    $discount = Discount::factory()->create([
        'code' => 'EXISTING',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
    ]);

    Livewire::actingAs($user)
        ->test(Form::class, ['discount' => $discount])
        ->assertSet('code', 'EXISTING')
        ->assertSet('valueType', 'percent')
        ->assertSet('valueAmount', '20');
});

test('discount validation requires code for code type', function () {
    $ctx = createStoreContext();

    Livewire::actingAs($ctx['user'])
        ->test(Form::class)
        ->set('type', 'code')
        ->set('code', '')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors('code');
});

test('discount validation requires value amount for non-free-shipping', function () {
    $ctx = createStoreContext();

    Livewire::actingAs($ctx['user'])
        ->test(Form::class)
        ->set('type', 'code')
        ->set('code', 'TESTCODE')
        ->set('valueType', 'percent')
        ->set('valueAmount', '')
        ->set('startsAt', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors('valueAmount');
});
