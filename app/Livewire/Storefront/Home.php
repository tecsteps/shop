<?php

namespace App\Livewire\Storefront;

use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Home extends Component
{
    public function render(ThemeSettingsService $themeSettings): View
    {
        $settings = $themeSettings->all();

        return view('livewire.storefront.home', [
            'settings' => $settings,
            'sections' => $settings['sections'],
            'featuredCollections' => $this->featuredCollections($settings),
            'featuredProducts' => $this->featuredProducts($settings),
        ]);
    }

    /**
     * The collections featured on the home page, configured via the
     * featured_collection_handles theme setting (max 4).
     *
     * @param  array<string, mixed>  $settings
     * @return SupportCollection<int, Collection>
     */
    protected function featuredCollections(array $settings): SupportCollection
    {
        $handles = $settings['featured_collection_handles'];

        $query = Collection::query()
            ->published()
            ->with(['products' => fn ($query) => $query->published()->with('media')->limit(1)]);

        $collections = $handles === []
            ? $query->orderBy('title')->limit(4)->get()
            : $query->whereIn('handle', $handles)->get()
                ->sortBy(fn (Collection $collection): int => (int) array_search($collection->handle, $handles, true))
                ->values();

        return $collections->take(4);
    }

    /**
     * The products featured on the home page, sourced from a configured
     * collection or falling back to the latest published products.
     *
     * @param  array<string, mixed>  $settings
     * @return SupportCollection<int, Product>
     */
    protected function featuredProducts(array $settings): SupportCollection
    {
        $count = max(4, min(8, (int) $settings['featured_products_count']));

        $sourceCollection = filled($settings['featured_products_collection_handle'])
            ? Collection::query()->published()->where('handle', $settings['featured_products_collection_handle'])->first()
            : null;

        $query = $sourceCollection !== null
            ? $sourceCollection->products()->published()
            : Product::query()->published()->orderByDesc('published_at')->orderByDesc('id');

        return $query
            ->with(['variants.inventoryItem', 'media'])
            ->limit($count)
            ->get();
    }
}
