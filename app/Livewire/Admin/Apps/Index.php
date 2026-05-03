<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\App;
use App\Models\AppInstallation;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public function installApp(int $appId): void
    {
        AppInstallation::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $this->currentStore()->id,
                'app_id' => $appId,
            ],
            [
                'scopes_json' => ['read-products', 'read-orders'],
                'status' => 'active',
                'installed_at' => now(),
            ],
        );

        $this->notify('App installed.');
    }

    public function uninstallApp(int $installationId): void
    {
        AppInstallation::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)
            ->whereKey($installationId)
            ->firstOrFail()
            ->forceFill(['status' => 'uninstalled'])
            ->save();

        $this->notify('App uninstalled.');
    }

    public function render(): View
    {
        $installedApps = AppInstallation::withoutGlobalScopes()
            ->with('app')
            ->where('store_id', $this->currentStore()->id)
            ->where('status', '!=', 'uninstalled')
            ->latest('installed_at')
            ->get();
        $installedAppIds = $installedApps->pluck('app_id');

        return view('livewire.admin.apps.index', [
            'installedApps' => $installedApps,
            'availableApps' => App::query()
                ->where('status', 'active')
                ->whereNotIn('id', $installedAppIds)
                ->oldest('name')
                ->get(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Apps',
        ]);
    }
}
