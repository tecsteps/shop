<?php

namespace App\Livewire\Admin\Apps;

use App\Enums\StoreUserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\App;
use App\Models\AppInstallation;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    public function mount(): void
    {
        $this->authorizeStore();
    }

    public function install(int $appId): void
    {
        $this->authorizeStore([StoreUserRole::Owner, StoreUserRole::Admin]);
        App::query()->findOrFail($appId);
        AppInstallation::query()->firstOrCreate(['store_id' => $this->currentStore()->getKey(), 'app_id' => $appId], ['scopes_json' => [], 'status' => 'active', 'installed_at' => now()]);
        $this->toast('App installed.');
    }

    public function uninstall(int $installationId): void
    {
        $this->authorizeStore([StoreUserRole::Owner, StoreUserRole::Admin]);
        AppInstallation::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($installationId)->update(['status' => 'uninstalled']);
        $this->toast('App uninstalled.');
    }

    #[Computed]
    public function apps()
    {
        return App::query()->with(['installations' => fn ($query) => $query->where('store_id', $this->currentStore()->getKey())])->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.admin.apps.index');
    }
}
