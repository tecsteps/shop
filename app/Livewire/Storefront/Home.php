<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;
use Livewire\Component;

class Home extends Component
{
    /**
     * Render the home page with sections ordered and toggled by theme settings.
     */
    public function render(ThemeSettingsService $settings): View
    {
        $sections = collect($settings->get('sections_order', []))
            ->filter(fn (string $section): bool => (bool) $settings->get("{$section}.enabled", false))
            ->values();

        return view('livewire.storefront.home', [
            'sections' => $sections,
            'hero' => $settings->get('hero', []),
            'featuredCollections' => $sections->contains('featured_collections')
                ? $this->featuredCollections($settings)
                : collect(),
            'featuredProducts' => $sections->contains('featured_products')
                ? $this->featuredProducts($settings)
                : collect(),
            'richTextHtml' => $sections->contains('rich_text') ? $settings->get('rich_text.html') : null,
        ])
            ->layout('storefront.layouts.app', [
                'metaDescription' => $settings->get('seo.description'),
            ])
            ->title(app('current_store')->name);
    }

    /**
     * Collections picked in theme settings, falling back to the newest ones.
     *
     * @return SupportCollection<int, Collection>
     */
    private function featuredCollections(ThemeSettingsService $settings): SupportCollection
    {
        $handles = array_filter($settings->get('featured_collections.collection_handles', []));
        $count = max(1, (int) $settings->get('featured_collections.count', 3));

        $query = Collection::query()->where('status', CollectionStatus::Active);

        if ($handles !== []) {
            return $query->whereIn('handle', $handles)->limit($count)->get();
        }

        return $query->latest()->limit($count)->get();
    }

    /**
     * Products from the configured collection, falling back to the newest
     * visible products of the store.
     *
     * @return SupportCollection<int, Product>
     */
    private function featuredProducts(ThemeSettingsService $settings): SupportCollection
    {
        $count = max(1, (int) $settings->get('featured_products.count', 8));
        $collectionHandle = $settings->get('featured_products.collection_handle');

        if (is_string($collectionHandle) && $collectionHandle !== '') {
            $collection = Collection::query()
                ->where('handle', $collectionHandle)
                ->where('status', CollectionStatus::Active)
                ->first();

            if ($collection !== null) {
                return $collection->products()
                    ->visible()
                    ->with(['variants.inventoryItem', 'media'])
                    ->limit($count)
                    ->get();
            }
        }

        return Product::query()
            ->visible()
            ->with(['variants.inventoryItem', 'media'])
            ->latest()
            ->limit($count)
            ->get();
    }
}
