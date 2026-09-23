<?php

use App\Livewire\Admin\Discounts\Form;
use App\Livewire\Admin\Discounts\Index;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

it('creates and edits a store discount with its eligibility and usage rules', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $this->actingAs($user);

    $product = Product::factory()->create(['store_id' => $store->id, 'title' => 'Blue Shirt']);
    $collection = Collection::create([
        'store_id' => $store->id,
        'title' => 'Summer collection',
        'handle' => 'summer-collection',
        'type' => 'manual',
        'status' => 'active',
    ]);

    $form = Livewire::test(Form::class)
        ->set('title', 'Summer offer')
        ->set('code', 'summer20')
        ->set('valueAmount', 101)
        ->call('save')
        ->assertHasErrors('valueAmount');

    $form->set('valueAmount', 20)
        ->set('minimumPurchaseAmount', 5000)
        ->set('specificProductIds', [$product->id])
        ->set('specificCollectionIds', [$collection->id])
        ->set('usageLimit', 40)
        ->set('onePerCustomer', true)
        ->set('startsAt', '2026-09-01T09:30')
        ->set('endsAt', '2026-12-31T23:59')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.discounts'));

    $discount = Discount::query()->where('code', 'SUMMER20')->firstOrFail();
    expect($discount->store_id)->toBe($store->id)
        ->and($discount->title)->toBe('Summer offer')
        ->and($discount->type)->toBe('percentage')
        ->and($discount->value)->toBe(20)
        ->and($discount->minimum_subtotal_amount)->toBe(5000)
        ->and($discount->usage_limit)->toBe(40)
        ->and($discount->starts_at->format('Y-m-d H:i'))->toBe('2026-09-01 09:30')
        ->and($discount->ends_at->format('Y-m-d H:i'))->toBe('2026-12-31 23:59')
        ->and($discount->is_active)->toBeTrue()
        ->and($discount->rules_json)->toMatchArray([
            'activation_method' => 'code',
            'one_per_customer' => true,
            'product_ids' => [$product->id],
            'collection_ids' => [$collection->id],
        ]);

    $discount->forceFill(['usage_count' => 7])->save();
    $edit = Livewire::test(Form::class, ['discount' => $discount->id])
        ->assertSet('type', 'code')
        ->assertSet('specificProductIds', [$product->id])
        ->set('title', 'Holiday offer')
        ->call('save')
        ->assertHasNoErrors()
        ->set('title', 'Holiday offer')
        ->set('type', 'automatic')
        ->set('valueType', 'fixed_amount')
        ->set('valueAmount', 750)
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.discounts'));

    $discount->refresh();
    expect($discount->title)->toBe('Holiday offer')
        ->and($discount->code)->toBeNull()
        ->and($discount->type)->toBe('fixed_amount')
        ->and($discount->value)->toBe(750)
        ->and($discount->usage_count)->toBe(7)
        ->and($discount->is_active)->toBeFalse()
        ->and($discount->rules_json['activation_method'])->toBe('automatic');
});

it('hides another stores discount from an authorized admin', function () {
    $firstStore = shopStore();
    $foreignDiscount = Discount::withoutGlobalScopes()->create([
        'store_id' => $firstStore->id,
        'code' => 'FOREIGN10',
        'title' => 'Private discount',
        'type' => 'percentage',
        'value' => 10,
        'starts_at' => now(),
        'is_active' => true,
    ]);

    $currentStore = shopStore('second-shop.test');
    app()->instance('current_store', $currentStore);
    $user = User::factory()->create();
    $user->stores()->attach($currentStore->id, ['role' => 'owner']);
    $this->actingAs($user);

    expect(fn () => Livewire::test(Form::class)
        ->set('discountId', $foreignDiscount->id)
        ->set('title', 'Changed by another store')
        ->call('save'))
        ->toThrow(ModelNotFoundException::class);

    expect(Discount::withoutGlobalScopes()->findOrFail($foreignDiscount->id)->title)->toBe('Private discount');
});

it('requires a store role with discount management permission', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'support']);
    $this->actingAs($user);

    Livewire::test(Form::class)->assertStatus(403);
});

it('allows support staff to see discounts without showing write controls', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'support']);
    $discount = Discount::factory()->for($store)->create(['code' => 'SUPPORT10', 'title' => 'Support-visible offer']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Support-visible offer')
        ->assertDontSee('Create discount')
        ->assertDontSee('Deactivate')
        ->assertDontSee('Delete');
});

it('filters discounts by code and active, scheduled, and expired status', function () {
    $store = shopStore();
    app()->instance('current_store', $store);
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    $this->actingAs($user);
    $now = now()->startOfDay()->setTime(12, 0);
    $this->travelTo($now);

    Discount::factory()->for($store)->create([
        'title' => 'Available offer', 'code' => 'SAVE20', 'is_active' => true,
        'starts_at' => $now->copy()->subDay(), 'ends_at' => $now->copy()->addDay(),
    ]);
    Discount::factory()->for($store)->create([
        'title' => 'Upcoming offer', 'code' => 'LATER20', 'is_active' => true,
        'starts_at' => $now->copy()->addDay(), 'ends_at' => null,
    ]);
    Discount::factory()->for($store)->create([
        'title' => 'Past offer', 'code' => 'OLD20', 'is_active' => true,
        'starts_at' => $now->copy()->subDays(3), 'ends_at' => $now->copy()->subDay(),
    ]);

    Livewire::actingAs($user)->test(Index::class)
        ->set('search', 'SAVE')
        ->set('statusFilter', 'active')
        ->assertSee('SAVE20')
        ->assertDontSee('LATER20')
        ->set('search', '')
        ->set('statusFilter', 'scheduled')
        ->assertSee('LATER20')
        ->assertDontSee('SAVE20')
        ->set('statusFilter', 'expired')
        ->assertSee('OLD20')
        ->assertDontSee('LATER20');
});
