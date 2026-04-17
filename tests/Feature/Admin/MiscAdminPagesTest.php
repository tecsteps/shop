<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Page;
use App\Models\Store;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function miscSetup(StoreUserRole $role = StoreUserRole::Owner): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    DB::table('store_users')->insert([
        'store_id' => $store->getKey(),
        'user_id' => $user->getKey(),
        'role' => $role->value,
        'created_at' => now(),
    ]);
    session(['current_store_id' => $store->getKey()]);

    return [$store, $user];
}

it('lists customers', function () {
    [$store, $user] = miscSetup();
    Customer::factory()->create(['store_id' => $store->getKey(), 'email' => 'shopper@example.com']);

    $this->actingAs($user)->get('/admin/customers')
        ->assertOk()
        ->assertSee('shopper@example.com');
});

it('shows customer detail', function () {
    [$store, $user] = miscSetup();
    $customer = Customer::factory()->create(['store_id' => $store->getKey(), 'email' => 'shopper@example.com']);

    $this->actingAs($user)->get('/admin/customers/'.$customer->getKey())
        ->assertOk()
        ->assertSee('shopper@example.com');
});

it('lists discounts', function () {
    [$store, $user] = miscSetup();
    Discount::factory()->create(['store_id' => $store->getKey(), 'code' => 'SAVE10']);

    $this->actingAs($user)->get('/admin/discounts')
        ->assertOk()
        ->assertSee('SAVE10');
});

it('opens discount create form', function () {
    [, $user] = miscSetup();

    $this->actingAs($user)->get('/admin/discounts/create')
        ->assertOk()
        ->assertSee('Create discount');
});

it('lists pages', function () {
    [$store, $user] = miscSetup();
    Page::factory()->create(['store_id' => $store->getKey(), 'title' => 'About Us']);

    $this->actingAs($user)->get('/admin/pages')
        ->assertOk()
        ->assertSee('About Us');
});

it('lists themes', function () {
    [$store, $user] = miscSetup();
    Theme::factory()->create(['store_id' => $store->getKey(), 'name' => 'Default']);

    $this->actingAs($user)->get('/admin/themes')
        ->assertOk()
        ->assertSee('Default');
});

it('shows general settings', function () {
    [, $user] = miscSetup();

    $this->actingAs($user)->get('/admin/settings')
        ->assertOk()
        ->assertSee('Store details');
});

it('shows staff settings', function () {
    [, $user] = miscSetup();

    $this->actingAs($user)->get('/admin/settings/staff')
        ->assertOk()
        ->assertSee('Staff');
});

it('denies staff from general settings', function () {
    [, $user] = miscSetup(StoreUserRole::Staff);

    $this->actingAs($user)->get('/admin/settings')
        ->assertForbidden();
});
