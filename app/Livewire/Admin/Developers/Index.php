<?php

namespace App\Livewire\Admin\Developers;

use App\Models\WebhookSubscription;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    #[Computed]
    public function webhooks(): mixed
    {
        $storeId = $this->getStoreId();

        if (! $storeId) {
            return collect();
        }

        return WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.admin.developers.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Developers']]]);
    }

    protected function getStoreId(): ?int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        return $store?->id;
    }
}
