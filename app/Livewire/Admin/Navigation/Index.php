<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationMenu;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Navigation')]
class Index extends Component
{
    public ?int $editingMenuId = null;

    public array $menuItems = [];

    public function selectMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->with('items')->findOrFail($menuId);
        Gate::authorize('update', app('current_store'));
        $this->editingMenuId = $menu->id;
        $this->menuItems = $menu->items->map(fn ($item) => ['label' => $item->label, 'type' => $item->type->value, 'url' => $item->url])->all();
    }

    public function addItem(): void
    {
        $this->menuItems[] = ['label' => '', 'type' => 'link', 'url' => ''];
    }

    public function removeItem(int $index): void
    {
        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    public function saveMenu(): void
    {
        $menu = NavigationMenu::query()->findOrFail($this->editingMenuId);
        Gate::authorize('update', app('current_store'));
        $this->validate(['menuItems' => ['array'], 'menuItems.*.label' => ['required', 'string', 'max:255'], 'menuItems.*.type' => ['required', 'string'], 'menuItems.*.url' => ['nullable', 'string', 'max:2048']]);
        $menu->items()->delete();
        foreach ($this->menuItems as $position => $item) {
            $menu->items()->create(['label' => $item['label'], 'type' => $item['type'], 'url' => $item['url'] ?: null, 'position' => $position]);
        }
        $this->dispatch('toast', type: 'success', message: 'Navigation saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.navigation.index', ['menus' => NavigationMenu::query()->with('items')->get()]);
    }
}
