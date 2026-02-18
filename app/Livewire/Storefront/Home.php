<?php

namespace App\Livewire\Storefront;

use App\Enums\CollectionStatus;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ThemeSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Home extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $themeSettings = [];

    public function mount(): void
    {
        $this->themeSettings = app(ThemeSettingsService::class)->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $collections = Collection::query()
            ->withoutGlobalScopes()
            ->where('status', CollectionStatus::Active)
            ->limit(3)
            ->get();

        $products = Product::query()
            ->withoutGlobalScopes()
            ->where('status', ProductStatus::Active)
            ->with(['variants' => fn ($q) => $q->where('is_default', true)])
            ->latest()
            ->limit(8)
            ->get();

        return view('livewire.storefront.home', [
            'collections' => $collections,
            'products' => $products,
        ]);
    }
}
