<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Illuminate\View\View;
use Livewire\Component;

class Home extends Component
{
    public string $newsletterEmail = '';

    public bool $newsletterSubscribed = false;

    public function subscribeToNewsletter(): void
    {
        $this->validate([
            'newsletterEmail' => ['required', 'email', 'max:255'],
        ]);

        $this->newsletterSubscribed = true;
        $this->newsletterEmail = '';
    }

    public function render(): View
    {
        $store = app('current_store');
        $themeSettings = app(ThemeSettingsService::class);
        $settings = $themeSettings->forStore($store);

        return view('livewire.storefront.home', [
            'store' => $store,
            'settings' => $settings,
            'sections' => $themeSettings->homeSections($settings),
            'collections' => Collection::query()
                ->where('status', CollectionStatus::Active)
                ->latest()
                ->limit((int) data_get($settings, 'home.featured_collections_count', 3))
                ->get(),
            'products' => Product::query()
                ->with('variants', 'media')
                ->where('status', ProductStatus::Active)
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit((int) data_get($settings, 'home.featured_products_count', 6))
                ->get(),
        ])->layout('storefront.layouts.app', [
            'title' => $store->name,
        ]);
    }
}
