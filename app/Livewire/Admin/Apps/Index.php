<?php

namespace App\Livewire\Admin\Apps;

use App\Enums\AppInstallationStatus;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);
        $this->storeId = $store->getKey();
    }

    public function uninstallApp(int $appId): void
    {
        $this->authorize('update', $this->scopedStore());

        AppInstallation::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->where('app_id', $appId)
            ->update(['status' => AppInstallationStatus::Uninstalled]);

        session()->flash('status', __('App uninstalled'));
        $this->dispatch('toast', type: 'success', message: __('App uninstalled'));
    }

    /**
     * @return Collection<int, AppInstallation>
     */
    public function installedApps(): Collection
    {
        return AppInstallation::withoutGlobalScopes()
            ->with(['app', 'webhookSubscriptions'])
            ->where('store_id', $this->storeId)
            ->whereNot('status', AppInstallationStatus::Uninstalled->value)
            ->latest('installed_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.apps.index', [
            'installedApps' => $this->installedApps(),
        ])->layout('layouts.app', [
            'title' => __('Apps'),
        ]);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->findOrFail($this->storeId);
    }
}
