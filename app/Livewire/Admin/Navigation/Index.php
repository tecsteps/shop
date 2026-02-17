<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Navigation')]
class Index extends Component
{
    public string $menuTitle = '';

    public string $menuHandle = '';

    public ?int $editingMenuId = null;

    public string $itemLabel = '';

    public string $itemUrl = '';

    public ?int $addingToMenuId = null;

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
        $this->modal('menu-form')->show();
    }

    public function saveMenu(): void
    {
        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'title' => $this->menuTitle,
            'handle' => $this->menuHandle ?: \Illuminate\Support\Str::slug($this->menuTitle),
        ];

        if ($this->editingMenuId) {
            NavigationMenu::where('id', $this->editingMenuId)->update($data);
        } else {
            NavigationMenu::create($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Menu saved.');
        $this->modal('menu-form')->close();
    }

    public function deleteMenu(int $menuId): void
    {
        $menu = NavigationMenu::findOrFail($menuId);
        $menu->items()->delete();
        $menu->delete();
        $this->dispatch('toast', type: 'success', message: 'Menu deleted.');
    }

    public function openItemModal(int $menuId): void
    {
        $this->addingToMenuId = $menuId;
        $this->itemLabel = '';
        $this->itemUrl = '';
        $this->modal('item-form')->show();
    }

    public function saveItem(): void
    {
        $maxPosition = NavigationItem::where('menu_id', $this->addingToMenuId)->max('position') ?? 0;

        NavigationItem::create([
            'menu_id' => $this->addingToMenuId,
            'type' => 'url',
            'label' => $this->itemLabel,
            'url' => $this->itemUrl,
            'position' => $maxPosition + 1,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Item added.');
        $this->modal('item-form')->close();
    }

    public function deleteItem(int $itemId): void
    {
        NavigationItem::where('id', $itemId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Item deleted.');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $store = app('current_store');
        $menus = NavigationMenu::where('store_id', $store->id)->with('items')->get();

        return view('livewire.admin.navigation.index', ['menus' => $menus]);
    }
}
