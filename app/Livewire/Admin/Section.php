<?php

namespace App\Livewire\Admin;

use App\Models\AnalyticsDaily;
use App\Models\AppInstallation;
use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\SearchSetting;
use App\Models\Theme;
use App\Models\WebhookSubscription;
use Livewire\Component;

class Section extends Component
{
    public string $heading = 'Store settings';

    public function mount(): void
    {
        $this->heading = match (true) {
            request()->is('admin/inventory') => 'Inventory',
            request()->is('admin/collections') => 'Collections',
            request()->is('admin/themes*') => 'Themes',
            request()->is('admin/pages') => 'Pages',
            request()->is('admin/navigation') => 'Navigation',
            request()->is('admin/apps*') => 'Apps',
            request()->is('admin/developers') => 'Developers',
            request()->is('admin/analytics') => 'Analytics',
            request()->is('admin/search/settings') => 'Search settings',
            default => $this->heading,
        };
    }

    public function render(): mixed
    {
        return view('livewire.admin.section', ['rows' => $this->rows()])->layout('layouts.admin');
    }

    /** @return list<array{title: string, subtitle: string, value: string}> */
    private function rows(): array
    {
        return match (true) {
            request()->is('admin/inventory') => InventoryItem::query()->with('variant.product')->latest()->take(50)->get()->map(fn (InventoryItem $item): array => ['title' => $item->variant?->product?->title ?? 'Unknown product', 'subtitle' => $item->variant?->title ?? 'Unknown variant', 'value' => $item->availableQuantity().' available'])->all(),
            request()->is('admin/collections*') => Collection::query()->withCount('products')->latest()->take(50)->get()->map(fn (Collection $collection): array => ['title' => $collection->title, 'subtitle' => $collection->status->value, 'value' => $collection->products_count.' products'])->all(),
            request()->is('admin/themes*') => Theme::query()->latest()->take(50)->get()->map(fn (Theme $theme): array => ['title' => $theme->name, 'subtitle' => 'Version '.$theme->version, 'value' => $theme->status->value])->all(),
            request()->is('admin/pages*') => Page::query()->latest()->take(50)->get()->map(fn (Page $page): array => ['title' => $page->title, 'subtitle' => '/pages/'.$page->handle, 'value' => $page->status->value])->all(),
            request()->is('admin/navigation') => NavigationMenu::query()->withCount('items')->latest()->take(50)->get()->map(fn (NavigationMenu $menu): array => ['title' => $menu->name, 'subtitle' => $menu->handle, 'value' => $menu->items_count.' links'])->all(),
            request()->is('admin/apps*') => AppInstallation::query()->with('app')->latest()->take(50)->get()->map(fn (AppInstallation $installation): array => ['title' => $installation->app?->name ?? 'Installed app', 'subtitle' => $installation->status, 'value' => 'Connected'])->all(),
            request()->is('admin/developers') => WebhookSubscription::query()->latest()->take(50)->get()->map(fn (WebhookSubscription $subscription): array => ['title' => $subscription->event, 'subtitle' => $subscription->target_url, 'value' => $subscription->status])->all(),
            request()->is('admin/analytics') => AnalyticsDaily::query()->latest('date')->take(30)->get()->map(fn (AnalyticsDaily $day): array => ['title' => $day->date->toDateString(), 'subtitle' => $day->visits_count.' visits', 'value' => $day->orders_count.' orders · €'.number_format($day->revenue_amount / 100, 2)])->all(),
            request()->is('admin/search/settings') => (($settings = SearchSetting::query()->first()) === null ? [] : [['title' => 'Search indexing', 'subtitle' => count($settings->synonyms ?? []).' synonym groups', 'value' => $settings->enabled ? 'Enabled' : 'Disabled']]),
            default => [],
        };
    }
}
