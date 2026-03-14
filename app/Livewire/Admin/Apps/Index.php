<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $store = app('current_store');

        $installations = AppInstallation::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('app')
            ->latest()
            ->get();

        return view('livewire.admin.apps.index', [
            'installations' => $installations,
        ])->layout('layouts.admin', ['title' => 'Apps']);
    }
}
