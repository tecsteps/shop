<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public ?NavigationMenu $editingMenu = null;

    /** @var array<int, array{label: string, type: string, url: string, resource_id: ?int}> */
    public array $menuItems = [];

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public function getMenusProperty()
    {
        return NavigationMenu::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->get();
    }

    public function selectMenu(int $menuId): void
    {
        $this->editingMenu = NavigationMenu::withoutGlobalScopes()
            ->with('items')
            ->findOrFail($menuId);

        $this->menuItems = $this->editingMenu->items
            ->sortBy('position')
            ->map(fn (NavigationItem $item) => [
                'label' => $item->label,
                'type' => $item->type,
                'url' => $item->url ?? '',
                'resource_id' => $item->resource_id,
            ])
            ->values()
            ->toArray();
    }

    public function addItem(): void
    {
        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->modal('item-form')->show();
    }

    public function editItem(int $index): void
    {
        $this->editingItemIndex = $index;
        $item = $this->menuItems[$index];
        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = $item['url'];
        $this->itemResourceId = $item['resource_id'];
        $this->modal('item-form')->show();
    }

    public function saveItem(): void
    {
        $item = [
            'label' => $this->itemLabel,
            'type' => $this->itemType,
            'url' => $this->itemType === 'link' ? $this->itemUrl : '',
            'resource_id' => $this->itemType !== 'link' ? $this->itemResourceId : null,
        ];

        if ($this->editingItemIndex !== null) {
            $this->menuItems[$this->editingItemIndex] = $item;
        } else {
            $this->menuItems[] = $item;
        }

        $this->modal('item-form')->close();
    }

    public function removeItem(int $index): void
    {
        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    public function saveMenu(): void
    {
        if (! $this->editingMenu) {
            return;
        }

        $this->editingMenu->items()->delete();

        foreach ($this->menuItems as $position => $item) {
            $this->editingMenu->items()->create([
                'label' => $item['label'],
                'type' => $item['type'],
                'url' => $item['url'] ?: null,
                'resource_id' => $item['resource_id'],
                'position' => $position,
            ]);
        }

        $this->dispatch('toast', type: 'success', message: 'Navigation saved.');
    }

    public function getAvailablePagesProperty()
    {
        return Page::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function getAvailableProductsProperty()
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->orderBy('title')
            ->limit(50)
            ->get(['id', 'title']);
    }

    public function getAvailableCollectionsProperty()
    {
        return \App\Models\Collection::withoutGlobalScopes()
            ->where('store_id', session('store_id'))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.navigation.index');
    }
}
