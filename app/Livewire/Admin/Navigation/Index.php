<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public ?int $selectedMenuId = null;

    public string $newMenuTitle = '';

    public string $newItemLabel = '';

    public string $newItemUrl = '';

    public string $newItemType = 'link';

    public ?int $editingItemId = null;

    public string $editItemLabel = '';

    public string $editItemUrl = '';

    public string $editItemType = 'link';

    #[Computed]
    public function menus(): \Illuminate\Database\Eloquent\Collection
    {
        return NavigationMenu::where('store_id', app('current_store')->id)
            ->with(['items' => fn ($q) => $q->orderBy('position')])
            ->get();
    }

    #[Computed]
    public function selectedMenu(): ?NavigationMenu
    {
        if (! $this->selectedMenuId) {
            return $this->menus->first();
        }

        return $this->menus->firstWhere('id', $this->selectedMenuId);
    }

    public function selectMenu(int $menuId): void
    {
        $this->selectedMenuId = $menuId;
        $this->editingItemId = null;
    }

    public function createMenu(): void
    {
        $this->validate([
            'newMenuTitle' => ['required', 'string', 'max:255'],
        ]);

        $store = app('current_store');

        NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => \Illuminate\Support\Str::slug($this->newMenuTitle),
            'title' => $this->newMenuTitle,
        ]);

        $this->newMenuTitle = '';
        $this->dispatch('toast', type: 'success', message: __('Menu created.'));
    }

    public function deleteMenu(int $menuId): void
    {
        NavigationMenu::where('store_id', app('current_store')->id)
            ->findOrFail($menuId)
            ->delete();

        if ($this->selectedMenuId === $menuId) {
            $this->selectedMenuId = null;
        }

        $this->dispatch('toast', type: 'success', message: __('Menu deleted.'));
    }

    public function addItem(): void
    {
        $menu = $this->selectedMenu;
        if (! $menu) {
            return;
        }

        $this->validate([
            'newItemLabel' => ['required', 'string', 'max:255'],
            'newItemUrl' => ['required', 'string', 'max:255'],
            'newItemType' => ['required', 'in:link,page,collection,product'],
        ]);

        $maxPosition = $menu->items()->max('position') ?? -1;

        NavigationItem::create([
            'menu_id' => $menu->id,
            'type' => NavigationItemType::from($this->newItemType),
            'label' => $this->newItemLabel,
            'url' => $this->newItemUrl,
            'position' => $maxPosition + 1,
        ]);

        $this->newItemLabel = '';
        $this->newItemUrl = '';
        $this->newItemType = 'link';
        $this->dispatch('toast', type: 'success', message: __('Item added.'));
    }

    public function editItem(int $itemId): void
    {
        $menu = $this->selectedMenu;
        if (! $menu) {
            return;
        }

        $item = $menu->items->firstWhere('id', $itemId);
        if (! $item) {
            return;
        }

        $this->editingItemId = $itemId;
        $this->editItemLabel = $item->label;
        $this->editItemUrl = $item->url ?? '';
        $this->editItemType = $item->type->value;
    }

    public function updateItem(): void
    {
        if (! $this->editingItemId) {
            return;
        }

        $this->validate([
            'editItemLabel' => ['required', 'string', 'max:255'],
            'editItemUrl' => ['required', 'string', 'max:255'],
            'editItemType' => ['required', 'in:link,page,collection,product'],
        ]);

        $menu = $this->selectedMenu;
        if (! $menu) {
            return;
        }

        $item = NavigationItem::where('menu_id', $menu->id)->findOrFail($this->editingItemId);
        $item->update([
            'label' => $this->editItemLabel,
            'url' => $this->editItemUrl,
            'type' => NavigationItemType::from($this->editItemType),
        ]);

        $this->editingItemId = null;
        $this->dispatch('toast', type: 'success', message: __('Item updated.'));
    }

    public function deleteItem(int $itemId): void
    {
        $menu = $this->selectedMenu;
        if (! $menu) {
            return;
        }

        NavigationItem::where('menu_id', $menu->id)->findOrFail($itemId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Item deleted.'));
    }

    public function moveItemUp(int $itemId): void
    {
        $this->reorderItem($itemId, -1);
    }

    public function moveItemDown(int $itemId): void
    {
        $this->reorderItem($itemId, 1);
    }

    private function reorderItem(int $itemId, int $direction): void
    {
        $menu = $this->selectedMenu;
        if (! $menu) {
            return;
        }

        $items = $menu->items->sortBy('position')->values();
        $index = $items->search(fn ($item) => $item->id === $itemId);

        if ($index === false) {
            return;
        }

        $swapIndex = $index + $direction;
        if ($swapIndex < 0 || $swapIndex >= $items->count()) {
            return;
        }

        $current = $items[$index];
        $swap = $items[$swapIndex];

        $currentPos = $current->position;
        $current->update(['position' => $swap->position]);
        $swap->update(['position' => $currentPos]);
    }

    public function render(): View
    {
        return view('livewire.admin.navigation.index');
    }
}
