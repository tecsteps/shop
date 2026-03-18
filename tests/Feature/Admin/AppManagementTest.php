<?php

use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Livewire\Admin\Apps\Show as AppsShow;
use App\Models\App;
use App\Models\AppInstallation;
use Livewire\Livewire;

beforeEach(function () {
    $this->ctx = createStoreContext();
    $this->actingAs($this->ctx['user']);
    session(['current_store_id' => $this->ctx['store']->id]);
});

it('renders the apps index page', function () {
    $this->get('/admin/apps')
        ->assertStatus(200)
        ->assertSee('Apps');
});

it('displays installed apps on the index', function () {
    $app = App::create(['name' => 'Test App', 'status' => 'active']);
    AppInstallation::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'app_id' => $app->id,
        'status' => 'active',
        'installed_at' => now(),
    ]);

    $component = Livewire::test(AppsIndex::class);
    $component->assertSee('Test App');
});

it('renders the app show page', function () {
    $app = App::create(['name' => 'Detail App', 'status' => 'active']);
    $installation = AppInstallation::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'app_id' => $app->id,
        'status' => 'active',
        'scopes_json' => ['read_products', 'write_orders'],
        'installed_at' => now(),
    ]);

    $this->get("/admin/apps/{$installation->id}")
        ->assertStatus(200)
        ->assertSee('Detail App');
});

it('displays app details and scopes', function () {
    $app = App::create(['name' => 'Scoped App', 'status' => 'active']);
    $installation = AppInstallation::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'app_id' => $app->id,
        'status' => 'active',
        'scopes_json' => ['read_products', 'write_orders'],
        'installed_at' => now(),
    ]);

    $component = Livewire::test(AppsShow::class, ['installation' => $installation]);
    $component->assertSee('Scoped App');
    $component->assertSee('read_products');
    $component->assertSee('write_orders');
});

it('can uninstall an app from the show page', function () {
    $app = App::create(['name' => 'Uninstall App', 'status' => 'active']);
    $installation = AppInstallation::withoutGlobalScopes()->create([
        'store_id' => $this->ctx['store']->id,
        'app_id' => $app->id,
        'status' => 'active',
        'installed_at' => now(),
    ]);

    $component = Livewire::test(AppsShow::class, ['installation' => $installation]);
    $component->call('uninstall');

    $installation->refresh();
    expect($installation->status)->toBe('uninstalled');
});

it('prevents access to another store installation', function () {
    $otherCtx = createStoreContext('other-store.test');
    $app = App::create(['name' => 'Other App', 'status' => 'active']);
    $installation = AppInstallation::withoutGlobalScopes()->create([
        'store_id' => $otherCtx['store']->id,
        'app_id' => $app->id,
        'status' => 'active',
        'installed_at' => now(),
    ]);

    // Restore original store context
    app()->instance('current_store', $this->ctx['store']);

    $this->get("/admin/apps/{$installation->id}")
        ->assertStatus(404);
});
