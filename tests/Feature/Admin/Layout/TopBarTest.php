<?php

use App\Livewire\Admin\Layout\TopBar;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('top bar component can be rendered', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertOk()
        ->assertSee($store->name);
});

test('top bar displays current store name', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['name' => 'My Test Store']);
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('My Test Store');
});

test('top bar displays user name in profile', function () {
    $user = User::factory()->create(['name' => 'John Admin']);
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('John Admin');
});

test('top bar can switch stores', function () {
    $user = User::factory()->create();
    $store1 = Store::factory()->create(['name' => 'Store One']);
    $store2 = Store::factory()->create(['name' => 'Store Two']);
    $user->stores()->attach($store1->id, ['role' => 'owner']);
    $user->stores()->attach($store2->id, ['role' => 'owner']);

    app()->instance('current_store', $store1);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSet('currentStoreName', 'Store One')
        ->call('switchStore', $store2->id)
        ->assertRedirect(route('admin.dashboard'));

    expect(session('current_store_id'))->toBe($store2->id);
});

test('top bar lists all available stores for the user', function () {
    $user = User::factory()->create();
    $store1 = Store::factory()->create(['name' => 'Store Alpha']);
    $store2 = Store::factory()->create(['name' => 'Store Beta']);
    $user->stores()->attach($store1->id, ['role' => 'owner']);
    $user->stores()->attach($store2->id, ['role' => 'owner']);

    app()->instance('current_store', $store1);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('Store Alpha')
        ->assertSee('Store Beta');
});

test('top bar contains dark mode toggle', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSeeHtml('Toggle dark mode');
});

test('top bar contains logout form', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(TopBar::class)
        ->assertSee('Log out');
});
