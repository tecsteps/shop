<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\StoreUserRole;
use App\Http\Middleware\CheckStoreRole;
use App\Http\Middleware\EnsureUserEmailIsVerified;
use App\Http\Middleware\ResolveStore;
use App\Livewire\Admin\Discounts\Form as AdminDiscountForm;
use App\Livewire\Admin\Discounts\Index as AdminDiscountsIndex;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function adminDiscountManagementStore(): Store
{
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
    app()->instance('current_store', $store);

    return $store;
}

function adminDiscountManagementUser(): User
{
    return User::query()->where('email', 'admin@acme.test')->firstOrFail();
}

function adminDiscountManagementSupportUser(Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), [
        'role' => StoreUserRole::Support->value,
        'created_at' => now(),
    ]);

    return $user;
}

test('livewire persists store middleware for admin action requests', function (): void {
    expect(Livewire::getPersistentMiddleware())
        ->toContain(EnsureUserEmailIsVerified::class)
        ->toContain(ResolveStore::class)
        ->toContain(CheckStoreRole::class);
});

test('admin discount routes require authentication and render store scoped discounts', function (): void {
    $store = adminDiscountManagementStore();
    $discount = Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'SAVE20',
        'value_type' => DiscountValueType::Percent,
        'value_amount' => 20,
        'usage_count' => 3,
        'usage_limit' => 100,
    ]);
    Discount::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
        'code' => 'OTHER20',
    ]);

    $this->get('/admin/discounts')->assertRedirect('/admin/login');

    $this->actingAs(adminDiscountManagementUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/discounts')
        ->assertSuccessful()
        ->assertSee('SAVE20')
        ->assertSee('20%')
        ->assertSee('3 / 100')
        ->assertDontSee('OTHER20');

    $this->actingAs(adminDiscountManagementUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/discounts/create')
        ->assertSuccessful()
        ->assertSee('Create discount');

    $this->actingAs(adminDiscountManagementUser())
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/discounts/'.$discount->getKey().'/edit')
        ->assertSuccessful()
        ->assertSee('SAVE20');
});

test('admin discount index filters by code and effective status', function (): void {
    $store = adminDiscountManagementStore();
    $user = adminDiscountManagementUser();
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'ACTIVE10',
    ]);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'SOON10',
        'starts_at' => now()->addWeek(),
    ]);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'DISABLEDSOON',
        'status' => DiscountStatus::Disabled,
        'starts_at' => now()->addWeek(),
    ]);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'ENDED10',
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'DISABLEDENDED',
        'status' => DiscountStatus::Disabled,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(AdminDiscountsIndex::class)
        ->assertSee('ACTIVE10')
        ->assertSee('SOON10')
        ->set('search', 'active')
        ->assertSee('ACTIVE10')
        ->assertDontSee('SOON10')
        ->set('search', '')
        ->set('statusFilter', 'scheduled')
        ->assertSee('SOON10')
        ->assertDontSee('ACTIVE10')
        ->assertDontSee('DISABLEDSOON')
        ->set('statusFilter', 'expired')
        ->assertSee('ENDED10')
        ->assertDontSee('DISABLEDENDED')
        ->set('statusFilter', 'all')
        ->set('typeFilter', 'automatic')
        ->assertDontSee('ACTIVE10')
        ->assertDontSee('SOON10');
});

test('admin discount form creates discounts with eligibility rules', function (): void {
    $store = adminDiscountManagementStore();
    $user = adminDiscountManagementUser();
    $product = Product::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();
    $collection = Collection::withoutGlobalScopes()->where('store_id', $store->getKey())->firstOrFail();

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class)
        ->set('type', 'code')
        ->set('code', 'VIP25')
        ->set('valueType', 'fixed')
        ->set('valueAmount', '5.00')
        ->set('minimumPurchaseAmount', '25.00')
        ->set('usageLimit', '100')
        ->set('onePerCustomer', true)
        ->call('addProduct', $product->getKey())
        ->call('addCollection', $collection->getKey())
        ->call('save')
        ->assertHasNoErrors();

    $discount = Discount::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('code', 'VIP25')
        ->firstOrFail();

    expect($discount->value_type)->toBe(DiscountValueType::Fixed)
        ->and($discount->value_amount)->toBe(500)
        ->and($discount->usage_limit)->toBe(100)
        ->and(data_get($discount->rules_json, 'min_purchase_amount'))->toBe(2500)
        ->and(data_get($discount->rules_json, 'one_per_customer'))->toBeTrue()
        ->and(data_get($discount->rules_json, 'applicable_product_ids'))->toBe([$product->getKey()])
        ->and(data_get($discount->rules_json, 'applicable_collection_ids'))->toBe([$collection->getKey()]);
});

test('admin discount form enforces mutation policies and verified users', function (): void {
    $store = adminDiscountManagementStore();
    $supportUser = adminDiscountManagementSupportUser($store);
    $unverifiedUser = User::factory()->unverified()->create();
    $unverifiedUser->stores()->attach($store->getKey(), [
        'role' => StoreUserRole::Admin->value,
        'created_at' => now(),
    ]);

    $this->actingAs($supportUser)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/discounts')
        ->assertSuccessful();

    $this->actingAs($supportUser)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/discounts/create')
        ->assertForbidden();

    $this->actingAs($unverifiedUser)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin/discounts/create')
        ->assertRedirect('/email/verify');

    Livewire::actingAs($supportUser)
        ->test(AdminDiscountForm::class)
        ->assertStatus(403);
});

test('admin discount form validates percentage values and normalized code uniqueness', function (): void {
    $store = adminDiscountManagementStore();
    $user = adminDiscountManagementUser();
    Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'SAVE20',
    ]);

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class)
        ->set('code', ' save20 ')
        ->set('valueType', 'percent')
        ->set('valueAmount', '101')
        ->call('save')
        ->assertHasErrors(['code', 'valueAmount']);

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class)
        ->set('code', 'UNIQUE20')
        ->set('valueType', 'percent')
        ->set('valueAmount', '10.5')
        ->call('save')
        ->assertHasErrors(['valueAmount']);
});

test('admin discount form creates drafts by default and preserves expired discounts', function (): void {
    $store = adminDiscountManagementStore();
    $user = adminDiscountManagementUser();

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class)
        ->set('code', 'DRAFT10')
        ->call('save')
        ->assertHasNoErrors();

    $draft = Discount::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('code', 'DRAFT10')
        ->firstOrFail();

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class, ['discount' => $draft])
        ->assertSet('isActive', false);

    $expired = Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'EXPIRED10',
        'status' => DiscountStatus::Expired,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class, ['discount' => $expired])
        ->assertSet('isActive', false)
        ->set('isActive', true)
        ->set('endsAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    expect($draft->status)->toBe(DiscountStatus::Draft)
        ->and($expired->refresh()->status)->toBe(DiscountStatus::Expired);
});

test('admin discount form edits discounts and rejects another store', function (): void {
    $store = adminDiscountManagementStore();
    $user = adminDiscountManagementUser();
    $discount = Discount::factory()->create([
        'store_id' => $store->getKey(),
        'code' => 'EDITME',
    ]);
    $otherDiscount = Discount::factory()->create([
        'store_id' => Store::factory()->create()->getKey(),
        'code' => 'OTHEREDIT',
    ]);

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class, ['discount' => $discount])
        ->set('type', 'automatic')
        ->set('valueType', 'free_shipping')
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($discount->refresh()->type)->toBe(DiscountType::Automatic)
        ->and($discount->code)->toBeNull()
        ->and($discount->value_type)->toBe(DiscountValueType::FreeShipping)
        ->and($discount->status)->toBe(DiscountStatus::Disabled);

    Livewire::actingAs($user)
        ->test(AdminDiscountForm::class, ['discount' => $otherDiscount])
        ->assertStatus(404);
});
