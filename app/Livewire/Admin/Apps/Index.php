<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\AppInstallation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Installed apps directory. Lists this store's app installations with an
 * uninstall action. Restricted to roles that may manage apps.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;

    public function mount(): void
    {
        if (! Gate::allows('manage-apps')) {
            abort(403);
        }
    }

    public function getInstalledAppsProperty()
    {
        return AppInstallation::query()
            ->with('app')
            ->where('store_id', app('current_store')->id)
            ->orderByDesc('installed_at')
            ->get();
    }

    public function uninstallApp(int $installationId): void
    {
        if (! Gate::allows('manage-apps')) {
            abort(403);
        }

        AppInstallation::query()
            ->where('store_id', app('current_store')->id)
            ->whereKey($installationId)
            ->update(['status' => 'uninstalled']);

        $this->dispatch('toast', type: 'success', message: __('App uninstalled'));
    }

    public function render()
    {
        return view('livewire.admin.apps.index');
    }
}
