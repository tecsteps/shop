<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public function getInstalledAppsProperty(): Collection
    {
        $store = app('current_store');

        return AppInstallation::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('app')
            ->latest('installed_at')
            ->get();
    }

    public function uninstallApp(int $installationId): void
    {
        $store = app('current_store');

        $installation = AppInstallation::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($installationId);

        $installation->update(['status' => 'uninstalled']);

        $this->dispatch('toast', type: 'success', message: __('App uninstalled successfully.'));
    }

    public function render(): View
    {
        return view('livewire.admin.apps.index');
    }
}
