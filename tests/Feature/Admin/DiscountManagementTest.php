<?php

use App\Models\Discount;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
    $this->actingAs($this->user);
    session()->put('current_store_id', $this->store->id);
});

it('renders discount list page', function () {
    $response = $this->get('/admin/discounts');

    $response->assertSuccessful();
    $response->assertSee('Discounts');
});

it('lists discounts belonging to the store', function () {
    Discount::factory()->count(3)->create([
        'store_id' => $this->store->id,
    ]);

    Livewire::test(\App\Livewire\Admin\Discounts\Index::class)
        ->assertSuccessful();
});

it('searches discounts by code', function () {
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'SUMMER20',
    ]);
    Discount::factory()->create([
        'store_id' => $this->store->id,
        'code' => 'WINTER10',
    ]);

    Livewire::test(\App\Livewire\Admin\Discounts\Index::class)
        ->set('search', 'SUMMER')
        ->assertSee('SUMMER20')
        ->assertDontSee('WINTER10');
});

it('renders discount create form', function () {
    $response = $this->get('/admin/discounts/create');

    $response->assertSuccessful();
    $response->assertSee('Create discount');
});

it('creates a percentage discount', function () {
    Livewire::test(\App\Livewire\Admin\Discounts\Form::class)
        ->set('type', 'code')
        ->set('code', 'NEWCODE25')
        ->set('valueType', 'percent')
        ->set('valueAmount', 25)
        ->set('status', 'active')
        ->set('startsAt', now()->subDay()->toDateTimeString())
        ->set('endsAt', now()->addMonth()->toDateTimeString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    expect(Discount::withoutGlobalScopes()->where('code', 'NEWCODE25')->exists())->toBeTrue();
});

it('generates a random discount code', function () {
    Livewire::test(\App\Livewire\Admin\Discounts\Form::class)
        ->call('generateCode')
        ->assertHasNoErrors();
});
