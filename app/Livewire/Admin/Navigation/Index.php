<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Navigation')]
class Index extends Component
{
    public bool $showMenuModal = false;

    public bool $showItemModal = false;

    public ?int $editingMenuId = null;

    public ?int $editingItemId = null;

    public ?int $itemMenuId = null;

    public string $menuTitle = '';

    public string $menuHandle = '';

    public string $itemLabel = '';

    public string $itemUrl = '';

    public string $itemType = 'link';

    public int $itemPosition = 0;

    public function openMenuModal(?int $menuId = null): void
    {
        $this->editingMenuId = $menuId;

        if ($menuId) {
            $menu = NavigationMenu::findOrFail($menuId);
            $this->menuTitle = $menu->title;
            $this->menuHandle = $menu->handle;
        } else {
            $this->menuTitle = '';
            $this->menuHandle = '';
        }

        $this->showMenuModal = true;
    }

    public function saveMenu(): void
    {
        $this->validate([
            'menuTitle' => ['required', 'string', 'max:255'],
            'menuHandle' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');

        if ($this->editingMenuId) {
            NavigationMenu::where('id', $this->editingMenuId)
                ->where('store_id', $store->id)
                ->update([
                    'title' => $this->menuTitle,
                    'handle' => $this->menuHandle,
                ]);
        } else {
            NavigationMenu::create([
                'store_id' => $store->id,
                'title' => $this->menuTitle,
                'handle' => $this->menuHandle,
            ]);
        }

        $this->showMenuModal = false;
        $this->dispatch('toast', type: 'success', message: 'Menu saved.');
    }

    public function deleteMenu(int $menuId): void
    {
        $store = app('current_store');

        NavigationMenu::where('id', $menuId)
            ->where('store_id', $store->id)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Menu deleted.');
    }

    public function openItemModal(int $menuId, ?int $itemId = null): void
    {
        $this->itemMenuId = $menuId;
        $this->editingItemId = $itemId;

        if ($itemId) {
            $item = NavigationItem::findOrFail($itemId);
            $this->itemLabel = $item->label;
            $this->itemUrl = $item->url ?? '';
            $this->itemType = $item->type->value;
            $this->itemPosition = $item->position;
        } else {
            $this->itemLabel = '';
            $this->itemUrl = '';
            $this->itemType = 'link';
            $this->itemPosition = NavigationItem::where('menu_id', $menuId)->max('position') + 1;
        }

        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemUrl' => ['nullable', 'string', 'max:500'],
            'itemType' => ['required', 'in:link,page,collection,product'],
            'itemPosition' => ['required', 'integer', 'min:0'],
        ]);

        $data = [
            'menu_id' => $this->itemMenuId,
            'label' => $this->itemLabel,
            'url' => $this->itemUrl,
            'type' => $this->itemType,
            'position' => $this->itemPosition,
        ];

        if ($this->editingItemId) {
            NavigationItem::where('id', $this->editingItemId)->update($data);
        } else {
            NavigationItem::create($data);
        }

        $this->showItemModal = false;
        $this->dispatch('toast', type: 'success', message: 'Navigation item saved.');
    }

    public function deleteItem(int $itemId): void
    {
        NavigationItem::where('id', $itemId)->delete();

        $this->dispatch('toast', type: 'success', message: 'Navigation item deleted.');
    }

    public function render(): View
    {
        $store = app('current_store');

        $menus = NavigationMenu::query()
            ->where('store_id', $store->id)
            ->with(['items' => fn ($q) => $q->orderBy('position')])
            ->get();

        return view('livewire.admin.navigation.index', [
            'menus' => $menus,
            'itemTypes' => NavigationItemType::cases(),
        ]);
    }
}
