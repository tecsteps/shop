<?php

use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('creates a percent discount', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(DiscountForm::class)
        ->set('type', 'code')
        ->set('code', 'SAVE10')
        ->set('valueType', 'percent')
        ->set('valueAmount', 10)
        ->set('status', 'active')
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    $discount = Discount::where('code', 'SAVE10')->first();
    expect($discount)->not->toBeNull()
        ->and($discount->value_type->value)->toBe('percent')
        ->and($discount->value_amount)->toBe(10);
});

it('creates a free shipping discount', function (): void {
    [$user, $store] = loginAsAdmin();

    Livewire::test(DiscountForm::class)
        ->set('type', 'code')
        ->set('code', 'FREESHIP')
        ->set('valueType', 'free_shipping')
        ->set('valueAmount', 0)
        ->set('status', 'active')
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    $discount = Discount::where('code', 'FREESHIP')->first();
    expect($discount)->not->toBeNull()
        ->and($discount->value_type->value)->toBe('free_shipping');
});

it('disables a discount', function (): void {
    [$user, $store] = loginAsAdmin();

    $discount = Discount::factory()->create([
        'store_id' => $store->id,
        'status' => 'active',
    ]);

    Livewire::test(DiscountForm::class, ['discount' => $discount])
        ->set('status', 'disabled')
        ->call('save')
        ->assertRedirect(route('admin.discounts.index'));

    expect($discount->fresh()->status->value)->toBe('disabled');
});
