<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public int $menuId = 0;

    public string $menuName = '';

    public string $menuHandle = '';

    public string $label = '';

    public string $url = '';

    public string $type = 'link';

    public function mount(): void
    {
        $menu = NavigationMenu::query()->first();
        $this->selectMenu($menu?->getKey() ?? 0);
    }

    public function selectMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->find($menuId);
        $this->menuId = $menu?->getKey() ?? 0;
        $this->menuName = $menu?->name ?? '';
        $this->menuHandle = $menu?->handle ?? '';
    }

    public function saveMenu(): void
    {
        $this->authorizeStoreManager();
        $data = $this->validate(['menuName' => ['required', 'string', 'max:255'], 'menuHandle' => ['required', 'string', 'max:255']]);
        $menu = $this->menuId > 0 ? NavigationMenu::query()->findOrFail($this->menuId) : new NavigationMenu;
        $menu->fill(['name' => $data['menuName'], 'handle' => $data['menuHandle']])->save();
        $this->selectMenu($menu->getKey());
    }

    public function addItem(): void
    {
        $this->authorizeStoreManager();
        $data = $this->validate(['menuId' => ['required', 'integer'], 'label' => ['required', 'string', 'max:255'], 'url' => ['required', 'string', 'max:500'], 'type' => ['required', 'string']]);
        $menu = NavigationMenu::query()->findOrFail($data['menuId']);
        $menu->items()->create(['label' => $data['label'], 'url' => $data['url'], 'type' => $data['type'], 'position' => (int) $menu->items()->max('position') + 1]);
        $this->reset(['label', 'url']);
    }

    public function deleteItem(int $itemId): void
    {
        $this->authorizeStoreManager();
        NavigationItem::query()->where('navigation_menu_id', $this->menuId)->findOrFail($itemId)->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.navigation.index', ['menus' => NavigationMenu::query()->with('items')->latest()->get(), 'menu' => $this->menuId > 0 ? NavigationMenu::query()->with('items')->find($this->menuId) : null])->layout('layouts.admin');
    }

    private function authorizeStoreManager(): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
    }
}
