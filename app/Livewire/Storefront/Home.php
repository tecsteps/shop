<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\QuickAddsProducts;
use App\Models\Collection;
use Illuminate\View\View;

class Home extends StorefrontComponent
{
    use QuickAddsProducts;

    public function render(): View
    {
        $store = $this->currentStore();
        $settings = $this->themeSettings();

        $collections = Collection::query()
            ->where('store_id', $store->getKey())
            ->where('status', 'active')
            ->with(['products' => fn ($query) => $query->where('status', 'active')->with('media')])
            ->limit(4)
            ->get();

        return $this->storefront(
            view('storefront.home', compact('settings', 'collections')),
            $store->name,
            (string) data_get($settings, 'meta_description', 'Shop the latest products from '.$store->name.'.'),
        );
    }
}
