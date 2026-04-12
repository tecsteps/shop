<?php

namespace App\Livewire\Admin\Apps;

use App\Models\App;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function install(int $appId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        AppInstallation::query()->updateOrCreate(
            ['store_id' => $store->id, 'app_id' => $appId],
            [
                'status' => 'active',
                'installed_at' => now(),
            ]
        );

        session()->flash('status', 'App installed.');
    }

    public function uninstall(int $appId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        AppInstallation::query()
            ->where('store_id', $store->id)
            ->where('app_id', $appId)
            ->update(['status' => 'uninstalled']);

        session()->flash('status', 'App uninstalled.');
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $installations = AppInstallation::query()
            ->where('store_id', $store->id)
            ->pluck('status', 'app_id');

        $apps = App::query()
            ->orderBy('name')
            ->get()
            ->map(function (App $app) use ($installations): App {
                $status = $installations->get($app->id);
                $app->setAttribute('installation_status', $status);

                return $app;
            });

        $installedApps = $apps->filter(fn (App $app) => $app->getAttribute('installation_status') === 'active')->values();
        $marketplaceApps = $apps->filter(fn (App $app) => $app->getAttribute('installation_status') !== 'active')->values();

        return view('livewire.admin.apps.index', [
            'installedApps' => $installedApps,
            'marketplaceApps' => $marketplaceApps,
        ]);
    }
}
