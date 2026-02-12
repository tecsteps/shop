<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Enums\ThemeStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Theme;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Home')]
class Home extends Component
{
    public function render()
    {
        $store = app('current_store');

        $theme = Theme::where('store_id', $store->id)
            ->where('status', ThemeStatus::Published)
            ->first();
        $settings = $theme?->settings?->settings_json ?? [];

        $hero = $settings['hero'] ?? [
            'heading' => 'Welcome to '.$store->name,
            'subheading' => 'Discover our curated selection of quality products.',
            'cta_text' => 'Shop Collections',
            'cta_link' => '/collections',
        ];

        $featuredCollections = Collection::where('store_id', $store->id)
            ->active()
            ->withCount(['products' => function ($query) {
                $query->where('status', ProductStatus::Active);
            }])
            ->limit(4)
            ->get();

        $featuredProducts = Product::where('store_id', $store->id)
            ->active()
            ->with(['variants.inventoryItem', 'media'])
            ->limit(4)
            ->get();

        return view('livewire.storefront.home', [
            'hero' => $hero,
            'featuredCollections' => $featuredCollections,
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
