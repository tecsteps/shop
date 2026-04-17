<?php

use App\Livewire\Admin\Apps\Index as AppsIndex;
use App\Models\App;
use App\Models\AppInstallation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('current_store');
});

it('installs an app', function (): void {
    [$user, $store] = loginAsAdmin();

    $app = App::create([
        'name' => 'Analytics Pro',
        'slug' => 'analytics-pro',
        'description' => 'Advanced analytics',
        'type' => 'first_party',
    ]);

    Livewire::test(AppsIndex::class)
        ->call('install', $app->id);

    $installation = AppInstallation::where('store_id', $store->id)
        ->where('app_id', $app->id)
        ->first();

    expect($installation)->not->toBeNull()
        ->and($installation->status)->toBe('active');
});

it('uninstalls an app', function (): void {
    [$user, $store] = loginAsAdmin();

    $app = App::create([
        'name' => 'Demo App',
        'slug' => 'demo-app',
        'type' => 'first_party',
    ]);

    AppInstallation::create([
        'store_id' => $store->id,
        'app_id' => $app->id,
        'status' => 'active',
        'installed_at' => now(),
    ]);

    Livewire::test(AppsIndex::class)
        ->call('uninstall', $app->id);

    $installation = AppInstallation::where('store_id', $store->id)
        ->where('app_id', $app->id)
        ->first();

    expect($installation->status)->toBe('uninstalled');
});
