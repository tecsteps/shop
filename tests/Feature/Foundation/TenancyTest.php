<?php

use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('phase one tables and seeded tenant records exist', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Schema::hasColumns('organizations', ['name', 'billing_email']))->toBeTrue()
        ->and(Schema::hasColumns('stores', ['organization_id', 'handle', 'status', 'default_currency']))->toBeTrue()
        ->and(Schema::hasColumns('store_domains', ['store_id', 'hostname', 'type', 'is_primary']))->toBeTrue()
        ->and(Schema::hasColumns('store_users', ['store_id', 'user_id', 'role']))->toBeTrue()
        ->and(Schema::hasColumns('store_settings', ['store_id', 'settings_json']))->toBeTrue()
        ->and(StoreDomain::query()->where('hostname', 'shop.test')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'admin@acme.test')->exists())->toBeTrue();
});

test('storefront requests resolve a store by hostname', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('http://shop.test/')
        ->assertOk();
});

test('storefront requests reject unknown or suspended stores', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('http://unknown-shop.test/')
        ->assertNotFound();

    $store = Store::factory()->suspended()->create();
    StoreDomain::factory()->create([
        'store_id' => $store->getKey(),
        'hostname' => 'suspended-shop.test',
    ]);

    $this->get('http://suspended-shop.test/')
        ->assertServiceUnavailable();
});

test('admin requests resolve the selected session store for authorized staff', function () {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', 'admin@acme.test')->firstOrFail();
    $store = Store::query()->where('handle', 'acme-fashion')->firstOrFail();

    $this->actingAs($user)
        ->withSession(['current_store_id' => $store->getKey()])
        ->get('/admin')
        ->assertOk();
});

test('admin login component authenticates active staff with a generic failure message', function () {
    $this->seed(DatabaseSeeder::class);

    Livewire::test(AdminLogin::class)
        ->set('email', 'admin@acme.test')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    Livewire::test(AdminLogin::class)
        ->set('email', 'admin@acme.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->assertAuthenticated();
});
