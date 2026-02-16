<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Navigation'])]
class Index extends Component
{
    public bool $showMenuModal = false;

    public bool $showItemModal = false;

    public ?int $editingMenuId = null;

    public string $menuName = '';

    public string $menuHandle = '';

    public ?int $activeMenuId = null;

    public string $itemTitle = '';

    public string $itemUrl = '';

    public function openMenuModal(?int $menuId = null): void
    {
        if ($menuId) {
            $menu = NavigationMenu::query()->find($menuId);
            $this->editingMenuId = $menuId;
            $this->menuName = $menu->name;
            $this->menuHandle = $menu->handle;
        } else {
            $this->editingMenuId = null;
            $this->menuName = '';
            $this->menuHandle = '';
        }
        $this->showMenuModal = true;
    }

    public function saveMenu(): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        $this->validate(['menuName' => ['required', 'string']]);

        if ($this->editingMenuId) {
            NavigationMenu::query()->where('id', $this->editingMenuId)->update([
                'name' => $this->menuName,
                'handle' => $this->menuHandle ?: \Illuminate\Support\Str::slug($this->menuName),
            ]);
        } else {
            NavigationMenu::query()->create([
                'store_id' => $store->id,
                'name' => $this->menuName,
                'handle' => $this->menuHandle ?: \Illuminate\Support\Str::slug($this->menuName),
            ]);
        }

        $this->showMenuModal = false;
        session()->flash('success', 'Menu saved.');
    }

    public function deleteMenu(int $menuId): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        NavigationMenu::query()->where('id', $menuId)->where('store_id', $store->id)->delete();
        session()->flash('success', 'Menu deleted.');
    }

    public function openItemModal(int $menuId): void
    {
        $this->activeMenuId = $menuId;
        $this->itemTitle = '';
        $this->itemUrl = '';
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        $this->validate([
            'itemTitle' => ['required', 'string'],
            'itemUrl' => ['required', 'string'],
        ]);

        NavigationItem::query()->create([
            'menu_id' => $this->activeMenuId,
            'title' => $this->itemTitle,
            'type' => 'url',
            'url' => $this->itemUrl,
            'position' => NavigationItem::query()->where('menu_id', $this->activeMenuId)->count(),
        ]);

        $this->showItemModal = false;
        session()->flash('success', 'Item added.');
    }

    public function deleteItem(int $itemId): void
    {
        $store = app('current_store');
        $this->authorize('update', $store);

        NavigationItem::query()->where('id', $itemId)->delete();
    }

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.navigation.index', [
            'menus' => NavigationMenu::query()
                ->where('store_id', $store->id)
                ->with(['items' => fn ($q) => $q->orderBy('position')])
                ->get(),
        ]);
    }
}
