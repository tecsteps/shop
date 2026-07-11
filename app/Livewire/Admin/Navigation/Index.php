<?php

namespace App\Livewire\Admin\Navigation;

use App\Livewire\Admin\AdminComponent;
use App\Models\NavigationMenu;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    public ?int $menuId = null;

    public string $menuTitle = '';

    public string $itemLabel = '';

    public string $itemUrl = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', NavigationMenu::class);
        $this->menuId = NavigationMenu::query()->where('store_id', $this->currentStore()->getKey())->value('id');
    }

    public function createMenu(): void
    {
        $this->authorizeWrite();
        $validated = $this->validate(['menuTitle' => ['required', 'string', 'max:255']]);
        $menu = NavigationMenu::create(['store_id' => $this->currentStore()->getKey(), 'title' => $validated['menuTitle'], 'handle' => Str::slug($validated['menuTitle'])]);
        $this->menuId = $menu->getKey();
        $this->reset('menuTitle');
        $this->toast('Menu created.');
    }

    public function addItem(): void
    {
        $this->authorizeWrite();
        $validated = $this->validate(['menuId' => ['required', 'integer'], 'itemLabel' => ['required', 'string', 'max:255'], 'itemUrl' => ['required', 'string', 'max:2048']]);
        $menu = NavigationMenu::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($validated['menuId']);
        $menu->items()->create(['type' => 'link', 'label' => $validated['itemLabel'], 'url' => $validated['itemUrl'], 'position' => $menu->items()->count()]);
        $this->reset('itemLabel', 'itemUrl');
        $this->toast('Navigation item added.');
    }

    public function removeItem(int $id): void
    {
        $this->authorizeWrite();
        $menu = NavigationMenu::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->menuId);
        $menu->items()->findOrFail($id)->delete();
        $this->toast('Navigation item removed.');
    }

    public function selectMenu(int $id): void
    {
        NavigationMenu::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        $this->menuId = $id;
    }

    #[Computed]
    public function menus()
    {
        return NavigationMenu::query()->where('store_id', $this->currentStore()->getKey())->with('items')->orderBy('title')->get();
    }

    private function authorizeWrite(): void
    {
        Gate::authorize('manage', NavigationMenu::class);
    }

    public function render()
    {
        return view('livewire.admin.navigation.index');
    }
}
