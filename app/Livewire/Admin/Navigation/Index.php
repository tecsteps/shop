<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingMenuId = null;

    /** @var array<int, array{id: int|null, title: string, type: string, url: string, resource_id: int|null, position: int}> */
    public array $menuItems = [];

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public ?int $editingItemIndex = null;

    public bool $showItemModal = false;

    public function selectMenu(int $menuId): void
    {
        $menu = NavigationMenu::with(['items' => function ($query) {
            $query->whereNull('parent_id')->orderBy('position');
        }])->findOrFail($menuId);

        $this->editingMenuId = $menu->id;
        $this->menuItems = $menu->items->map(function (NavigationItem $item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type->value,
                'url' => $item->url ?? '',
                'resource_id' => $item->resource_id,
                'position' => $item->position,
            ];
        })->all();
    }

    public function addItem(): void
    {
        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->showItemModal = true;
    }

    public function editItem(int $index): void
    {
        $item = $this->menuItems[$index];
        $this->editingItemIndex = $index;
        $this->itemLabel = $item['title'];
        $this->itemType = $item['type'];
        $this->itemUrl = $item['url'];
        $this->itemResourceId = $item['resource_id'];
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', 'string', 'in:link,page,collection,product'],
        ]);

        $itemData = [
            'id' => null,
            'title' => $this->itemLabel,
            'type' => $this->itemType,
            'url' => $this->itemType === 'link' ? $this->itemUrl : '',
            'resource_id' => $this->itemType !== 'link' ? $this->itemResourceId : null,
            'position' => 0,
        ];

        if ($this->editingItemIndex !== null) {
            $itemData['id'] = $this->menuItems[$this->editingItemIndex]['id'];
            $this->menuItems[$this->editingItemIndex] = $itemData;
        } else {
            $this->menuItems[] = $itemData;
        }

        $this->reindexPositions();
        $this->reset('itemLabel', 'itemType', 'itemUrl', 'itemResourceId', 'editingItemIndex', 'showItemModal');
    }

    public function removeItem(int $index): void
    {
        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
        $this->reindexPositions();
    }

    public function moveItemUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        $temp = $this->menuItems[$index - 1];
        $this->menuItems[$index - 1] = $this->menuItems[$index];
        $this->menuItems[$index] = $temp;
        $this->reindexPositions();
    }

    public function moveItemDown(int $index): void
    {
        if ($index >= count($this->menuItems) - 1) {
            return;
        }

        $temp = $this->menuItems[$index + 1];
        $this->menuItems[$index + 1] = $this->menuItems[$index];
        $this->menuItems[$index] = $temp;
        $this->reindexPositions();
    }

    public function saveMenu(): void
    {
        if (! $this->editingMenuId) {
            return;
        }

        $menu = NavigationMenu::findOrFail($this->editingMenuId);

        $existingIds = collect($this->menuItems)->pluck('id')->filter()->all();
        $menu->items()->whereNotIn('id', $existingIds)->delete();

        foreach ($this->menuItems as $index => $itemData) {
            $attrs = [
                'menu_id' => $menu->id,
                'title' => $itemData['title'],
                'type' => NavigationItemType::from($itemData['type']),
                'url' => $itemData['url'] ?: null,
                'resource_id' => $itemData['resource_id'],
                'position' => $index,
            ];

            if ($itemData['id']) {
                NavigationItem::where('id', $itemData['id'])->update($attrs);
            } else {
                NavigationItem::create($attrs);
            }
        }

        $this->dispatch('toast', type: 'success', message: 'Menu saved successfully.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Page>
     */
    public function getAvailablePages(): \Illuminate\Database\Eloquent\Collection
    {
        return Page::query()->select('id', 'title')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    public function getAvailableCollections(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()->select('id', 'title')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getAvailableProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()->select('id', 'title')->get();
    }

    private function reindexPositions(): void
    {
        foreach ($this->menuItems as $index => &$item) {
            $item['position'] = $index;
        }
    }

    public function render()
    {
        $menus = NavigationMenu::all();

        return view('livewire.admin.navigation.index', [
            'menus' => $menus,
            'availablePages' => $this->getAvailablePages(),
            'availableCollections' => $this->getAvailableCollections(),
            'availableProducts' => $this->getAvailableProducts(),
        ])->layout('layouts.admin', ['title' => 'Navigation']);
    }
}
