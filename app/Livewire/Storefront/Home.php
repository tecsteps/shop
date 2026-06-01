<?php

namespace App\Livewire\Storefront;

use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use App\Support\Storefront\ProductCardPresenter;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The storefront home page.
 *
 * Renders configurable sections (hero, featured collections, featured products,
 * newsletter, rich text) in the order and visibility defined by the active
 * theme settings. Section content (headings, collection handles, etc.) is also
 * sourced from theme settings, so merchants control the home page without code
 * changes.
 */
#[Layout('storefront.layouts.app')]
class Home extends Component
{
    public function render()
    {
        $settings = app(ThemeSettingsService::class);

        $sections = collect($settings->get('home.sections', []))
            ->filter(fn (array $section): bool => ($section['enabled'] ?? false) === true)
            ->pluck('type')
            ->values();

        return view('livewire.storefront.home', [
            'sections' => $sections,
            'hero' => $settings->get('home.hero', []),
            'featuredCollections' => $this->featuredCollections($settings),
            'featuredProducts' => $this->featuredProducts($settings),
            'newsletter' => $settings->get('home.newsletter', []),
            'richText' => $settings->get('home.rich_text.html', ''),
        ]);
    }

    /**
     * The collections to feature, resolved from configured handles (falling
     * back to the most recent active collections).
     *
     * @return Collection<int, ProductCollection>
     */
    private function featuredCollections(ThemeSettingsService $settings): Collection
    {
        $handles = (array) $settings->get('home.featured_collections.handles', []);
        $limit = (int) $settings->get('home.featured_collections.limit', 4);

        $query = ProductCollection::query()->published();

        if ($handles !== []) {
            $query->whereIn('handle', $handles);
        }

        return $query->limit($limit)->get();
    }

    /**
     * The products to feature, resolved from a configured collection handle
     * (falling back to recent active products), normalized for the card.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function featuredProducts(ThemeSettingsService $settings): Collection
    {
        $handle = $settings->get('home.featured_products.collection_handle');
        $limit = (int) $settings->get('home.featured_products.limit', 8);

        $collection = $handle
            ? ProductCollection::query()->published()->where('handle', $handle)->first()
            : null;

        $products = $collection !== null
            ? $collection->products()
                ->published()
                ->with(['variants.inventoryItem', 'media'])
                ->limit($limit)
                ->get()
            : Product::query()
                ->published()
                ->with(['variants.inventoryItem', 'media'])
                ->latest()
                ->limit($limit)
                ->get();

        return $products->map(fn (Product $product): array => ProductCardPresenter::fromProduct($product));
    }
}
