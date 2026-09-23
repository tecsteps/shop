<?php

namespace App\Livewire\Admin\Apps;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public ?int $storeId = null;

    /** @var Collection<int, object> */
    public Collection $installedApps;

    public function boot(): void
    {
        $this->restoreCurrentStore();
        Gate::authorize('manage-apps');
    }

    public function mount(): void
    {
        $this->storeId = $this->currentStore()->id;
        $this->refreshInstalledApps();
    }

    public function uninstallApp(int $appId): void
    {
        Gate::authorize('manage-apps');
        $store = $this->currentStore();
        $deleted = DB::table('app_installations')
            ->where('store_id', $store->id)
            ->where('app_id', $appId)
            ->delete();

        abort_if($deleted === 0, 404);

        $this->refreshInstalledApps();
        session()->flash('status', 'App uninstalled.');
    }

    public function render(): mixed
    {
        return view('livewire.admin.apps.index', [
            'installedApps' => $this->installedAppsForCurrentStore(),
        ])->layout('layouts.admin');
    }

    private function refreshInstalledApps(): void
    {
        $this->installedApps = $this->installedAppsForCurrentStore();
    }

    /** @return Collection<int, object> */
    private function installedAppsForCurrentStore(): Collection
    {
        return DB::table('app_installations')
            ->join('apps', 'apps.id', '=', 'app_installations.app_id')
            ->where('app_installations.store_id', $this->currentStore()->id)
            ->orderBy('apps.name')
            ->get([
                'apps.id',
                'app_installations.id as installation_id',
                'apps.name',
                'apps.description',
                'apps.scopes_json',
                'app_installations.status',
                'app_installations.created_at',
            ]);
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        abort_unless($store instanceof Store, 403);

        return $store;
    }

    private function restoreCurrentStore(): void
    {
        if (app()->bound('current_store') || $this->storeId === null) {
            return;
        }

        app()->instance('current_store', Store::query()->findOrFail($this->storeId));
    }
}
