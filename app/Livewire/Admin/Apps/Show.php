<?php

namespace App\Livewire\Admin\Apps;

use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $installationId;

    #[Locked]
    public int $storeId;

    public function boot(): void
    {
        $this->restoreCurrentStore();
        Gate::authorize('manage-apps');
    }

    public function mount(int $installation): void
    {
        $this->installationId = $installation;
        $this->storeId = $this->currentStore()->id;
        $this->installation();
    }

    public function render(): mixed
    {
        $installation = $this->installation();
        $settings = json_decode((string) $installation->settings_json, true) ?: [];
        $requestedScopes = json_decode((string) $installation->scopes_json, true) ?: [];
        $subscriptions = DB::table('webhook_subscriptions')
            ->where('store_id', $this->currentStore()->id)
            ->where('app_installation_id', $this->installationId)
            ->orderBy('event_type')
            ->get(['id', 'event_type', 'target_url', 'status']);

        return view('livewire.admin.apps.show', [
            'installation' => $installation,
            'grantedScopes' => data_get($settings, 'granted_scopes', $requestedScopes),
            'subscriptions' => $subscriptions,
            'apiCallCount' => 0,
            'lastApiCallAt' => null,
        ])->layout('layouts.admin');
    }

    private function installation(): object
    {
        return DB::table('app_installations')
            ->join('apps', 'apps.id', '=', 'app_installations.app_id')
            ->where('app_installations.store_id', $this->currentStore()->id)
            ->where('app_installations.id', $this->installationId)
            ->first([
                'app_installations.id as installation_id',
                'app_installations.store_id',
                'app_installations.status',
                'app_installations.settings_json',
                'app_installations.created_at',
                'apps.name',
                'apps.description',
                'apps.scopes_json',
            ]) ?? abort(404);
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        abort_unless($store instanceof Store, 403);

        return $store;
    }

    private function restoreCurrentStore(): void
    {
        if (app()->bound('current_store') || ! isset($this->storeId)) {
            return;
        }

        app()->instance('current_store', Store::query()->findOrFail($this->storeId));
    }
}
