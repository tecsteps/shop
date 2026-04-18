<?php

namespace App\Livewire\Admin\Apps;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function render()
    {
        $store = app('current_store');

        $installations = collect();
        if (class_exists(\App\Models\AppInstallation::class) && Schema::hasTable('app_installations')) {
            $installations = \App\Models\AppInstallation::query()
                ->where('store_id', $store->id)
                ->with('app')
                ->get();
        }

        return view('livewire.admin.apps.index', [
            'installations' => $installations,
        ]);
    }
}
