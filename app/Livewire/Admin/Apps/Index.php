<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Apps')]
class Index extends Component
{
    public function uninstall(int $installationId): void
    {
        $store = app('current_store');

        AppInstallation::query()
            ->where('store_id', $store->id)
            ->where('id', $installationId)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'App uninstalled.');
    }

    public function render(): View
    {
        $store = app('current_store');

        $installations = AppInstallation::query()
            ->where('store_id', $store->id)
            ->with('app')
            ->latest()
            ->get();

        return view('livewire.admin.apps.index', [
            'installations' => $installations,
        ]);
    }
}
