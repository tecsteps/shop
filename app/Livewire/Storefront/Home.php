<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\AnalyticsService;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;

class Home extends Component
{
    public string $heroHeading = '';

    public string $heroSubheading = '';

    public string $heroCtaText = '';

    public string $heroCtaLink = '';

    /** @var SupportCollection<int, Collection> */
    public SupportCollection $featuredCollections;

    /** @var SupportCollection<int, Product> */
    public SupportCollection $featuredProducts;

    public function mount(): void
    {
        $themeSettings = app(ThemeSettingsService::class);

        $this->heroHeading = $themeSettings->get('hero_heading', 'Welcome to our store');
        $this->heroSubheading = $themeSettings->get('hero_subheading', '');
        $this->heroCtaText = $themeSettings->get('hero_cta_text', 'Shop Now');
        $this->heroCtaLink = $themeSettings->get('hero_cta_link', '/collections');

        $handles = $themeSettings->get('featured_collection_handles', []);
        if (is_array($handles) && count($handles) > 0) {
            $this->featuredCollections = Collection::query()
                ->whereIn('handle', $handles)
                ->where('status', CollectionStatus::Active)
                ->get()
                ->sortBy(fn (Collection $c) => array_search($c->handle, $handles));
        } else {
            $this->featuredCollections = Collection::query()
                ->where('status', CollectionStatus::Active)
                ->limit(4)
                ->get();
        }

        $store = app()->bound('current_store') ? app('current_store') : null;
        if ($store) {
            app(AnalyticsService::class)->track(
                $store,
                'page_view',
                ['url' => '/'],
                session()->getId(),
                auth('customer')->id()
            );
        }

        $this->featuredProducts = Product::query()
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->with([
                'variants' => fn ($q) => $q->where('status', VariantStatus::Active),
                'variants.inventoryItem',
                'media',
            ])
            ->latest('published_at')
            ->limit(8)
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.home')
            ->layout('layouts.storefront');
    }
}
