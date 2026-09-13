<?php

namespace App\Livewire\Admin\Apps;

use App\Enums\AppInstallationStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\AppInstallation;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesAdminStore;

    public ?int $uninstallingId = null;

    public bool $showUninstallModal = false;

    public function mount(): void
    {
        Gate::authorize('manage-apps');
    }

    public function confirmUninstall(int $installationId): void
    {
        $this->uninstallingId = $installationId;
        $this->showUninstallModal = true;
    }

    public function uninstallApp(): void
    {
        Gate::authorize('manage-apps');

        $installation = AppInstallation::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->uninstallingId)
            ->firstOrFail();

        $installation->update(['status' => AppInstallationStatus::Uninstalled]);

        $this->showUninstallModal = false;
        $this->uninstallingId = null;
        $this->toast('success', 'App uninstalled.');
    }

    public function render()
    {
        return view('livewire.admin.apps.index', [
            'installedApps' => AppInstallation::query()
                ->where('store_id', $this->store()->getKey())
                ->where('status', '!=', AppInstallationStatus::Uninstalled)
                ->with('app')
                ->orderByDesc('installed_at')
                ->get(),
        ])->layout('livewire.admin.layout.app', ['title' => 'Apps']);
    }
}
