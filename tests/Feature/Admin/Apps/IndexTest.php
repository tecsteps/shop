<?php

use App\Livewire\Admin\Apps\Index;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function appsAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the apps index page', function () {
    [$user, $store] = appsAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Apps')
        ->assertSuccessful();
});

test('it lists installed apps for the current store', function () {
    [$user, $store] = appsAdmin();

    $app = App::factory()->create(['name' => 'Analytics Plus']);

    AppInstallation::factory()->create([
        'store_id' => $store->id,
        'app_id' => $app->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Analytics Plus');
});

test('it does not show apps from other stores', function () {
    [$user, $store] = appsAdmin();

    $otherStore = Store::factory()->create();

    $myApp = App::factory()->create(['name' => 'My App']);
    $otherApp = App::factory()->create(['name' => 'Other App']);

    AppInstallation::factory()->create([
        'store_id' => $store->id,
        'app_id' => $myApp->id,
    ]);

    AppInstallation::factory()->create([
        'store_id' => $otherStore->id,
        'app_id' => $otherApp->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('My App')
        ->assertDontSee('Other App');
});

test('it uninstalls an app', function () {
    [$user, $store] = appsAdmin();

    $app = App::factory()->create(['name' => 'Uninstall Me']);

    $installation = AppInstallation::factory()->create([
        'store_id' => $store->id,
        'app_id' => $app->id,
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('uninstall', $installation->id)
        ->assertDispatched('toast');

    expect(AppInstallation::find($installation->id))->toBeNull();
});

test('it shows empty state when no apps are installed', function () {
    [$user, $store] = appsAdmin();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No apps installed');
});
