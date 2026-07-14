<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\AdminComponent;
use App\Models\App;
use App\Models\AppInstallation;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Index extends AdminComponent
{
    public function mount(): void
    {
        $this->authorizeApps();
    }

    public function installApp(int $appId): void
    {
        $this->authorizeApps();
        $app = App::query()->where('status', 'active')->findOrFail($appId);
        AppInstallation::withoutGlobalScopes()->updateOrCreate([
            'store_id' => $this->currentStore()->id, 'app_id' => $app->id,
        ], [
            'scopes_json' => ['read_products', 'read_orders'], 'status' => 'active', 'installed_at' => now(),
        ]);
        unset($this->installedApps, $this->availableApps);
        $this->toast('App installed');
    }

    public function uninstallApp(int $appId): void
    {
        $this->authorizeApps();
        $installation = AppInstallation::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)->where('app_id', $appId)->firstOrFail();
        $installation->update(['status' => 'uninstalled']);
        unset($this->installedApps, $this->availableApps);
        $this->toast('App uninstalled');
    }

    #[Computed]
    public function installedApps(): mixed
    {
        return AppInstallation::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)
            ->where('status', '!=', 'uninstalled')->with('app')->orderByDesc('installed_at')->get();
    }

    #[Computed]
    public function availableApps(): mixed
    {
        $installedIds = AppInstallation::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)
            ->where('status', '!=', 'uninstalled')->pluck('app_id');

        return App::query()->where('status', 'active')->whereNotIn('id', $installedIds)->orderBy('name')->get();
    }

    public function render(): View
    {
        return $this->admin(view('admin.apps.index'), 'Apps', [['label' => 'Apps']]);
    }

    private function authorizeApps(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }
}
