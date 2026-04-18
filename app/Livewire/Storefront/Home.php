<?php

namespace App\Livewire\Storefront;

use App\Services\ThemeSettingsService;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    public function render(ThemeSettingsService $themeSettings)
    {
        $settings = $themeSettings->forStore();

        $featuredCollections = $this->loadFeaturedCollections((array) ($settings['featured_collection_handles'] ?? []));
        $featuredProducts = $this->loadFeaturedProducts((array) ($settings['featured_product_handles'] ?? []));

        return view('livewire.storefront.home', [
            'themeSettings' => $settings,
            'featuredCollections' => $featuredCollections,
            'featuredProducts' => $featuredProducts,
        ]);
    }

    /**
     * @param  array<int, string>  $handles
     * @return array<int, array<string, mixed>>
     */
    protected function loadFeaturedCollections(array $handles): array
    {
        if (! Schema::hasTable('collections') || ! app()->bound('current_store')) {
            return [];
        }

        $storeId = app('current_store')->id;

        $query = \DB::table('collections')
            ->where('store_id', $storeId)
            ->where('status', 'active');

        if (! empty($handles)) {
            $query->whereIn('handle', $handles);
        }

        return $query->limit(6)->get(['id', 'title', 'handle'])->map(fn ($row): array => [
            'id' => $row->id,
            'title' => $row->title,
            'handle' => $row->handle,
        ])->all();
    }

    /**
     * @param  array<int, string>  $handles
     * @return array<int, array<string, mixed>>
     */
    protected function loadFeaturedProducts(array $handles): array
    {
        if (! Schema::hasTable('products') || ! app()->bound('current_store')) {
            return [];
        }

        $storeId = app('current_store')->id;

        $query = \DB::table('products')
            ->where('store_id', $storeId)
            ->where('status', 'active');

        if (! empty($handles)) {
            $query->whereIn('handle', $handles);
        }

        return $query->limit(8)->get(['id', 'title', 'handle'])->map(fn ($row): array => [
            'id' => $row->id,
            'title' => $row->title,
            'handle' => $row->handle,
        ])->all();
    }
}
