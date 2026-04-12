<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $newMenuTitle = '';

    public ?int $activeMenuId = null;

    #[Validate('required|string|in:link,page,collection,product')]
    public string $newItemType = 'link';

    #[Validate('required|string|max:255')]
    public string $newItemLabel = '';

    #[Validate('nullable|string|max:2048')]
    public string $newItemUrl = '';

    #[Validate('nullable|integer')]
    public ?int $newItemResourceId = null;

    public bool $showItemModal = false;

    public function createMenu(): void
    {
        $this->validateOnly('newMenuTitle');

        /** @var Store $store */
        $store = app('current_store');

        NavigationMenu::create([
            'store_id' => $store->id,
            'title' => $this->newMenuTitle,
            'handle' => Str::slug($this->newMenuTitle),
        ]);

        $this->reset('newMenuTitle');
        session()->flash('status', 'Menu created.');
    }

    public function deleteMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->findOrFail($menuId);
        $menu->delete();
        session()->flash('status', 'Menu deleted.');
    }

    public function openItemModal(int $menuId): void
    {
        $this->activeMenuId = $menuId;
        $this->reset('newItemType', 'newItemLabel', 'newItemUrl', 'newItemResourceId');
        $this->newItemType = 'link';
        $this->showItemModal = true;
    }

    public function closeItemModal(): void
    {
        $this->showItemModal = false;
        $this->activeMenuId = null;
    }

    public function addItem(): void
    {
        $this->validate([
            'newItemType' => 'required|string|in:link,page,collection,product',
            'newItemLabel' => 'required|string|max:255',
            'newItemUrl' => 'nullable|string|max:2048',
            'newItemResourceId' => 'nullable|integer',
        ]);

        if ($this->activeMenuId === null) {
            return;
        }

        $menu = NavigationMenu::query()->findOrFail($this->activeMenuId);

        $position = (int) ($menu->items()->max('position') ?? -1) + 1;

        NavigationItem::create([
            'menu_id' => $menu->id,
            'type' => $this->newItemType,
            'label' => $this->newItemLabel,
            'url' => $this->newItemType === 'link' ? ($this->newItemUrl !== '' ? $this->newItemUrl : null) : null,
            'resource_id' => $this->newItemType !== 'link' ? $this->newItemResourceId : null,
            'position' => $position,
        ]);

        $this->closeItemModal();
        session()->flash('status', 'Item added.');
    }

    public function deleteItem(int $itemId): void
    {
        $item = NavigationItem::query()->findOrFail($itemId);
        $item->delete();
        session()->flash('status', 'Item deleted.');
    }

    public function moveItem(int $itemId, string $direction): void
    {
        $item = NavigationItem::query()->findOrFail($itemId);

        $sibling = NavigationItem::query()
            ->where('menu_id', $item->menu_id)
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('position', '<', $item->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $item->position)->orderBy('position'),
            )
            ->first();

        if ($sibling === null) {
            return;
        }

        $itemPosition = $item->position;
        $item->update(['position' => $sibling->position]);
        $sibling->update(['position' => $itemPosition]);
    }

    public function render(): View
    {
        $menus = NavigationMenu::query()
            ->with('items')
            ->orderBy('title')
            ->get();

        return view('livewire.admin.navigation.index', [
            'menus' => $menus,
        ]);
    }
}
