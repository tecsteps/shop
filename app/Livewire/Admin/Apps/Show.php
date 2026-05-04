<?php

namespace App\Livewire\Admin\Apps;

use App\Enums\AppInstallationStatus;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    #[Locked]
    public int $installationId;

    public function mount(AppInstallation $installation): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $installation = AppInstallation::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereKey($installation->getKey())
            ->first();

        abort_unless($installation instanceof AppInstallation, 404);

        $this->storeId = $store->getKey();
        $this->installationId = $installation->getKey();
    }

    public function uninstallApp(): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->installation()->forceFill([
            'status' => AppInstallationStatus::Uninstalled,
        ])->save();

        session()->flash('status', __('App uninstalled'));
        $this->dispatch('toast', type: 'success', message: __('App uninstalled'));
    }

    public function render()
    {
        return view('livewire.admin.apps.show', [
            'installation' => $this->installation(),
        ])->layout('layouts.app', [
            'title' => __('App detail'),
        ]);
    }

    private function installation(): AppInstallation
    {
        return AppInstallation::withoutGlobalScopes()
            ->with(['app', 'oauthTokens', 'webhookSubscriptions.deliveries'])
            ->where('store_id', $this->storeId)
            ->findOrFail($this->installationId);
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
