<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Collection;
use Livewire\Component;

class Home extends Component
{
    use ResolvesStore;

    public function render()
    {
        $store = $this->store();
        $settings = $this->themeSettings();

        $collections = Collection::query()
            ->where('store_id', $store->getKey())
            ->where('status', CollectionStatus::Active)
            ->orderBy('title')
            ->take((int) ($settings['home']['featured_collections_count'] ?? 4))
            ->get();

        return view('livewire.storefront.home', [
            'store' => $store,
            'settings' => $settings,
            'collections' => $collections,
        ])->layout('storefront.layouts.app', [
            'title' => $store->name,
            'metaDescription' => 'Welcome to '.$store->name,
        ]);
    }
}
