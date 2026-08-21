<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function uninstall(int $installationId): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
        AppInstallation::query()->findOrFail($installationId)->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.apps.index', ['installations' => AppInstallation::query()->with('app')->latest()->get(), 'availableApps' => \App\Models\App::query()->whereDoesntHave('installations', fn ($query) => $query->where('store_id', app('current_store')->getKey()))->get()])->layout('layouts.admin');
    }
}
