<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    #[Computed]
    public function installedApps(): mixed
    {
        $storeId = $this->getStoreId();

        if (! $storeId) {
            return collect();
        }

        return AppInstallation::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->with('app')
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.admin.apps.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Apps']]]);
    }

    protected function getStoreId(): ?int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store?->id;
    }
}
