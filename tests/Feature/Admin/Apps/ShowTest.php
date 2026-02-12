<?php

use App\Livewire\Admin\Apps\Show;
use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Store;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function appsShowAdmin(): array
{
    $store = Store::factory()->create();
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);

    app()->instance('current_store', $store);

    return [$user, $store];
}

test('it renders the app show page', function () {
    [$user, $store] = appsShowAdmin();

    $app = App::factory()->create(['name' => 'My Integration']);

    $installation = AppInstallation::factory()->create([
        'store_id' => $store->id,
        'app_id' => $app->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['installation' => $installation])
        ->assertSee('My Integration')
        ->assertSuccessful();
});

test('it displays installation details', function () {
    [$user, $store] = appsShowAdmin();

    $app = App::factory()->create([
        'name' => 'Detail App',
        'description' => 'A helpful integration',
    ]);

    $installation = AppInstallation::factory()->create([
        'store_id' => $store->id,
        'app_id' => $app->id,
        'status' => 'active',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['installation' => $installation])
        ->assertSee('Detail App')
        ->assertSee('A helpful integration')
        ->assertSee('Active');
});

test('it uninstalls the app and redirects', function () {
    [$user, $store] = appsShowAdmin();

    $app = App::factory()->create();

    $installation = AppInstallation::factory()->create([
        'store_id' => $store->id,
        'app_id' => $app->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['installation' => $installation])
        ->call('uninstall')
        ->assertDispatched('toast')
        ->assertRedirect(route('admin.apps.index'));

    expect(AppInstallation::find($installation->id))->toBeNull();
});

test('it aborts if installation belongs to a different store', function () {
    [$user, $store] = appsShowAdmin();

    $otherStore = Store::factory()->create();
    $app = App::factory()->create();

    $installation = AppInstallation::factory()->create([
        'store_id' => $otherStore->id,
        'app_id' => $app->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['installation' => $installation])
        ->assertNotFound();
});
