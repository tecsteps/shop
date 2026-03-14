<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Livewire\Component;

class Show extends Component
{
    public AppInstallation $installation;

    public function mount(int $app): void
    {
        $store = app('current_store');

        $this->installation = AppInstallation::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('app')
            ->findOrFail($app);
    }

    public function render()
    {
        return view('livewire.admin.apps.show')
            ->layout('layouts.admin', ['title' => $this->installation->app->name]);
    }
}
