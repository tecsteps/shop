<?php

use App\Enums\AppInstallationStatus;
use App\Livewire\Admin\Apps\Index;
use App\Models\App;
use App\Models\AppInstallation;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    ['store' => $this->store, 'user' => $this->user] = $this->createStoreContext();
    $this->actingAsAdmin($this->user, $this->store);
});

it('lists installed apps scoped to the current store', function () {
    $app = App::factory()->create(['name' => 'Reviews Pro']);
    AppInstallation::factory()->create([
        'store_id' => $this->store->getKey(),
        'app_id' => $app->getKey(),
        'status' => AppInstallationStatus::Active,
    ]);

    $this->get(route('admin.apps.index'))
        ->assertOk()
        ->assertSee('Reviews Pro');
});

it('shows app detail with scopes', function () {
    $app = App::factory()->create(['name' => 'Reviews Pro']);
    $installation = AppInstallation::factory()->create([
        'store_id' => $this->store->getKey(),
        'app_id' => $app->getKey(),
        'status' => AppInstallationStatus::Active,
        'scopes_json' => ['read-products', 'read-orders'],
    ]);

    $this->get(route('admin.apps.show', $installation))
        ->assertOk()
        ->assertSee('read-products');
});

it('uninstalls an app', function () {
    $installation = AppInstallation::factory()->create([
        'store_id' => $this->store->getKey(),
        'status' => AppInstallationStatus::Active,
    ]);

    Livewire::test(Index::class)
        ->call('confirmUninstall', $installation->getKey())
        ->call('uninstallApp')
        ->assertDispatched('toast');

    expect($installation->refresh()->status)->toBe(AppInstallationStatus::Uninstalled);
});

it('returns 404 for an installation from another store', function () {
    ['store' => $otherStore] = $this->createStoreContext();
    $installation = AppInstallation::factory()->create(['store_id' => $otherStore->getKey()]);

    $this->get(route('admin.apps.show', $installation))->assertNotFound();
});
